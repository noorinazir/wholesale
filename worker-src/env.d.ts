/// <reference types="@cloudflare/workers-types" />

export interface Env {
  LARAVEL_APP: DurableObjectNamespace;
  APP_KEY?: string;
  KIMI_API_KEY?: string;
}
