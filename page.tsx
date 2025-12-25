// src/app/page.tsx
import fs from "fs";
import path from "path";

export default function Home() {
  const filePath = path.join(process.cwd(), "public", "b.html");
  const html = fs.readFileSync(filePath, "utf8");

  return (
    <html>
      <head />
      <body dangerouslySetInnerHTML={{ __html: html }} />
    </html>
  );
}
