# Deploying to Cloudflare Containers

## Prerequisites

1. **Cloudflare account** with Containers beta access
2. **Wrangler CLI** installed: `npm install -g wrangler`
3. **Authenticated with Cloudflare**: `wrangler login`
4. **Node.js 20+** installed

## Architecture

```
User Request → Cloudflare Worker (wrangler.jsonc) → Laravel Container (Dockerfile)
                                                      ↓
                                                   Apache + PHP 8.3 on port 8080
                                                      ↓
                                                   SQLite at /tmp/database.sqlite
```

- **Worker** (`worker-src/index.ts`): Routes all requests to a singleton container
- **Container** (`Dockerfile`): PHP 8.3 + Apache running Laravel
- **Database**: SQLite in `/tmp` (ephemeral — resets on container restart)
- **Container sleeps** after 30 min inactivity, cold starts in ~2-3s

## Steps

### 1. Install Worker Dependencies

```bash
cd worker-src
npm install
cd ..
```

### 2. Set Secrets

Set your APP_KEY and API keys as Wrangler secrets (not in the config file):

```bash
# Generate a Laravel APP_KEY locally
php artisan key:generate --show

# Set it as a secret
wrangler secret put APP_KEY
# Paste the generated key (base64:...)

# Set Kimi API key if needed
wrangler secret put KIMI_API_KEY
# Paste your Moonshot API key
```

### 3. Deploy

```bash
wrangler deploy
```

This will:
- Build the Worker
- Build the Docker image and push it to Cloudflare
- Create the Durable Object + Container binding
- Deploy globally

### 4. Access Your App

After deploy, Wrangler will output a URL like:
```
https://wholesale-outreach.<your-subdomain>.workers.dev
```

**Login**: `admin@wholesale.com` / `password`

## Important Notes

### Ephemeral Database ⚠️
The SQLite database lives in `/tmp` and **resets every time the container restarts** (cold start after sleep). This means:
- All data (vendors, products, POs, etc.) is lost on restart
- Only the admin user is re-seeded on each startup via `start.sh`
- For production, migrate to **Cloudflare D1** (managed SQLite) or an external database

### Upgrading to D1 (Recommended for Persistence)
1. Create a D1 database: `wrangler d1 create wholesale-outreach`
2. Add binding to `wrangler.jsonc`:
   ```jsonc
   "d1_databases": [
     { "binding": "DB", "database_name": "wholesale-outreach", "database_id": "<id>" }
   ]
   ```
3. Set `DB_CONNECTION=sqlite` and configure D1 in Laravel's `config/database.php`
4. Run migrations: `wrangler d1 execute wholesale-outreach --file=database/migrations/*.sql`

### Changing Instance Type
Edit `wrangler.jsonc`:
- `basic` — 1/4 vCPU, 1 GiB RAM, 4 GB disk (free tier equivalent)
- `standard-1` — 1/2 vCPU, 4 GiB RAM, 8 GB disk
- `standard-2` — 1 vCPU, 6 GiB RAM, 12 GB disk

### Stopping the Container
The container auto-sleeps after 30 min of inactivity. To manually stop:
```bash
wrangler containers stop wholesale-outreach
```

## Files Created

| File | Purpose |
|------|---------|
| `wrangler.jsonc` | Cloudflare Worker + Container config |
| `worker-src/index.ts` | Worker that routes requests to the Laravel container |
| `worker-src/env.d.ts` | TypeScript types for Worker env |
| `worker-src/package.json` | Worker npm dependencies |
| `Dockerfile` | PHP 8.3 + Apache container image |
| `start.sh` | Container startup script (migrate, seed, start Apache) |
| `.dockerignore` | Excludes unnecessary files from container image |
| `render.yaml` | Alternative Render deployment (kept for reference) |
