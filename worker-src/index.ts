import { Container } from "@cloudflare/containers";
import type { Env } from "./env.d.ts";

export class LaravelContainer extends Container {
  defaultPort = 8080;
  sleepAfter = "30m";
  enableInternet = true;

  envVars = {
    APP_ENV: "production",
    APP_DEBUG: "false",
    APP_URL: "https://wholesale-outreach.containers.cloudflare.com",
    DB_CONNECTION: "sqlite",
    DB_DATABASE: "/tmp/database.sqlite",
    SESSION_DRIVER: "file",
    QUEUE_CONNECTION: "sync",
    CACHE_STORE: "file",
    LOG_CHANNEL: "stderr",
    MAIL_MAILER: "log",
    KIMI_MODEL: "moonshot-v1-8k",
    KIMI_BASE_URL: "https://api.moonshot.cn/v1",
  };
}

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    const container = env.LARAVEL_APP.getByName("singleton");
    const secrets: Record<string, string> = {};
    if (env.APP_KEY) secrets.APP_KEY = env.APP_KEY;
    if (env.KIMI_API_KEY) secrets.KIMI_API_KEY = env.KIMI_API_KEY;
    await container.startAndWaitForPorts({
      ports: [8080],
      startOptions: Object.keys(secrets).length > 0 ? { envVars: secrets } : undefined,
    });
    return container.fetch(request);
  },
} satisfies ExportedHandler<Env>;
