import type { Express } from "express";
import type { Server } from "http";
import { createProxyMiddleware, fixRequestBody } from "http-proxy-middleware";

/**
 * All /api/* requests are proxied to the PHP backend running on port 8001.
 * The PHP backend (php/router.php) handles every API endpoint using PDO + PostgreSQL.
 *
 * fixRequestBody re-streams the request body after express.json() has parsed it,
 * so POST/PUT bodies are forwarded correctly to PHP.
 */
export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {

  app.use(
    "/api",
    createProxyMiddleware({
      target: "http://localhost:8001",
      changeOrigin: true,
      on: {
        proxyReq: fixRequestBody,
        error: (_err, _req, res: any) => {
          res.status(503).json({ message: "PHP backend unavailable. Is php/router.php running?" });
        },
      },
    })
  );

  return httpServer;
}
