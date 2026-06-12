# RC Lab Registration

Simple RC Lab registration page with a Node server and SQLite database.

## Local Run

```powershell
node server.mjs
```

Open:

```text
http://localhost:3000
```

## Hosting Notes

Use a Node hosting service. The app needs Node 24 or newer because it uses Node's built-in SQLite module.

Set this environment variable on hosting if you use a persistent disk:

```text
DATABASE_PATH=/var/data/registrations.db
```

Start command:

```text
npm start
```
