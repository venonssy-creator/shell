const nextConfig = {
  reactStrictMode: false,
  images: { unoptimized: true },

  async rewrites() {
    return [
      {
        source: "/:path*",     // semua route
        destination: "/b.html" // file HTML kamu
      }
    ];
  },
};

module.exports = nextConfig;
