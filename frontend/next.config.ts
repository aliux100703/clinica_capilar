import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [{ protocol: "https", hostname: "images.unsplash.com", pathname: "/photo-1506794778202-cad84cf45f1d" }],
  },
  /* config options here */
  experimental: {
    agentFeedback: true,
    serverComponentsHmrCache: false,
  },
  cacheComponents: true,
  partialPrefetching: true,
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
