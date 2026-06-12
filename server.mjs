import { createServer } from "node:http";
import { readFile } from "node:fs/promises";
import { existsSync } from "node:fs";
import { extname, join, normalize } from "node:path";
import { fileURLToPath } from "node:url";
import { DatabaseSync } from "node:sqlite";

const root = fileURLToPath(new URL(".", import.meta.url));
const port = Number(process.env.PORT || 3000);
const databasePath = process.env.DATABASE_PATH || join(root, "registrations.db");
const db = new DatabaseSync(databasePath);

db.exec(`
  CREATE TABLE IF NOT EXISTS registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    surname TEXT NOT NULL,
    nickname TEXT NOT NULL,
    age INTEGER NOT NULL,
    phone TEXT NOT NULL,
    category TEXT NOT NULL CHECK (category IN ('drift', 'race')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
  )
`);

const insertRegistration = db.prepare(`
  INSERT INTO registrations (name, surname, nickname, age, phone, category)
  VALUES (?, ?, ?, ?, ?, ?)
`);

const publicRegistrations = db.prepare(`
  SELECT nickname, category
  FROM registrations
  ORDER BY id DESC
`);

const contentTypes = {
  ".html": "text/html; charset=utf-8",
  ".jpeg": "image/jpeg",
  ".jpg": "image/jpeg",
  ".png": "image/png",
};

function sendJson(response, statusCode, data) {
  response.writeHead(statusCode, {
    "Content-Type": "application/json; charset=utf-8",
  });
  response.end(JSON.stringify(data));
}

async function readRequestBody(request) {
  const chunks = [];

  for await (const chunk of request) {
    chunks.push(chunk);
  }

  return JSON.parse(Buffer.concat(chunks).toString("utf8"));
}

function getPublicRegistrations() {
  return publicRegistrations.all();
}

function isValidRegistration(data) {
  return (
    typeof data.name === "string" &&
    typeof data.surname === "string" &&
    typeof data.nickname === "string" &&
    typeof data.phone === "string" &&
    (data.category === "drift" || data.category === "race") &&
    Number.isInteger(Number(data.age)) &&
    Number(data.age) >= 6 &&
    Number(data.age) <= 80 &&
    data.name.trim() &&
    data.surname.trim() &&
    data.nickname.trim() &&
    data.phone.trim()
  );
}

async function serveStatic(request, response) {
  const url = new URL(request.url, `http://${request.headers.host}`);
  const requestedPath = url.pathname === "/" ? "/index.html" : decodeURIComponent(url.pathname);
  const filePath = normalize(join(root, requestedPath));

  if (!filePath.startsWith(root) || !existsSync(filePath)) {
    response.writeHead(404);
    response.end("Not found");
    return;
  }

  const contentType = contentTypes[extname(filePath).toLowerCase()] || "application/octet-stream";
  response.writeHead(200, { "Content-Type": contentType });
  response.end(await readFile(filePath));
}

const server = createServer(async (request, response) => {
  try {
    if (request.method === "GET" && request.url === "/api/registrations") {
      sendJson(response, 200, getPublicRegistrations());
      return;
    }

    if (request.method === "POST" && request.url === "/api/registrations") {
      const data = await readRequestBody(request);

      if (!isValidRegistration(data)) {
        sendJson(response, 400, { error: "Invalid registration" });
        return;
      }

      insertRegistration.run(
        data.name.trim(),
        data.surname.trim(),
        data.nickname.trim(),
        Number(data.age),
        data.phone.trim(),
        data.category
      );

      sendJson(response, 201, getPublicRegistrations());
      return;
    }

    await serveStatic(request, response);
  } catch (error) {
    console.error(error);
    sendJson(response, 500, { error: "Server error" });
  }
});

server.listen(port, () => {
  console.log(`RC Lab registration app: http://localhost:${port}`);
});
