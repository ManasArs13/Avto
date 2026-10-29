import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // В Docker собираем самодостаточный сервер (см. docker/web/Dockerfile).
  output: process.env.NEXT_OUTPUT === "standalone" ? "standalone" : undefined,
  cacheComponents: true,
  partialPrefetching: true,
  // Общий пакет с типами и клиентом API лежит в TS-исходниках.
  transpilePackages: ["@avto/shared"],
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
