/**
 * After `next build` (static export), copy the generated site into the PHP project
 * so Laragon can serve it at /admin-stats/ alongside admin-dashboard.html.
 */
const fs = require("fs");
const path = require("path");

const appRoot = path.join(__dirname, "..");
const outDir = path.join(appRoot, "out");
const nested = path.join(outDir, "admin-stats");
const targetDir = path.join(appRoot, "..", "admin-stats");

function rmrf(dir) {
  if (!fs.existsSync(dir)) return;
  fs.rmSync(dir, { recursive: true, force: true });
}

if (!fs.existsSync(outDir)) {
  console.error("Missing out/ — run `next build` first.");
  process.exit(1);
}

const source = fs.existsSync(nested) ? nested : outDir;

rmrf(targetDir);
fs.cpSync(source, targetDir, { recursive: true });
console.log("Embedded static dashboard →", targetDir);
