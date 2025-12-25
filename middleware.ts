import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

export function middleware(req: NextRequest) {
  const path = req.nextUrl.pathname;

  // biarkan asset & api normal
  if (
    path.startsWith("/_next") ||
    path.startsWith("/api") ||
    path.includes(".")
  ) {
    return NextResponse.next();
  }

  return NextResponse.rewrite(new URL("/b.html", req.url));
}

// ⬇️ INI KUNCINYA
export const config = {
  matcher: "/:path*",
};
