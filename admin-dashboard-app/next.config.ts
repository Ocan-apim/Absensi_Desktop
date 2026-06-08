import type { NextConfig } from "next";
import { readFileSync } from "fs";
import { resolve } from "path";

/** Static files are copied to ../admin-stats (served next to admin-dashboard.html). */
const nextConfig: NextConfig = {
  trailingSlash: true,
  images: { unoptimized: true },
  // HTTPS configuration for dev server
  devIndicators: {
    position: "bottom-right",
  },
  experimental: {
    serverMinification: false,
  },
};

export default nextConfig;
