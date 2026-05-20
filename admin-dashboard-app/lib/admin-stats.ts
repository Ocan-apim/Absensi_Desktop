import type { DayPoint, JurusanCode, JurusanSlice, TimestampPoint } from "./dashboard-data";
import { JURUSAN_COLORS, JURUSAN_ORDER } from "./dashboard-data";

export type AdminStatsMeta = {
  chart_month: string;
  month_range: { start: string; end: string };
  week_range: { start: string; end: string };
  total_siswa: number;
  siswa_tanpa_jurusan_terpetakan: number;
};

export type AdminStatsPayload = {
  meta: AdminStatsMeta;
  areaByDay: DayPoint[];
  weekBars: { guru: TimestampPoint[]; siswa: TimestampPoint[] };
  jurusan: { name: JurusanCode; count: number; value: number }[];
};

function pad2(n: number) {
  return String(n).padStart(2, "0");
}

export function formatChartMonth(d: Date) {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}`;
}

export function formatDateIso(d: Date) {
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}

export function getAdminStatsUsername(): string | null {
  if (typeof window === "undefined") {
    return process.env.NEXT_PUBLIC_ADMIN_STATS_USERNAME?.trim() || null;
  }
  try {
    const raw = sessionStorage.getItem("absensi_auth");
    if (raw) {
      const j = JSON.parse(raw) as { role?: string; username?: string };
      if (j.role === "admin" && j.username) {
        return String(j.username).trim();
      }
    }
  } catch {
    /* ignore */
  }
  const env = process.env.NEXT_PUBLIC_ADMIN_STATS_USERNAME?.trim();
  return env || null;
}

export function getPhpApiBase(): string | null {
  const env = process.env.NEXT_PUBLIC_PHP_API_BASE?.trim();
  if (typeof window !== "undefined") {
    if (env) return env;
    return window.location.origin;
  }
  return env || null;
}

export function jurusanToPieSlices(
  rows: { name: string; value: number }[]
): JurusanSlice[] {
  const allowed = new Set(JURUSAN_ORDER as unknown as string);
  return rows
    .filter((r) => allowed.has(r.name))
    .map((r) => ({
      name: r.name as JurusanCode,
      value: r.value,
      fill: JURUSAN_COLORS[r.name as JurusanCode],
    }));
}

export async function fetchAdminStats(params: {
  username: string;
  chartMonth: string;
  weekStart: string;
}): Promise<AdminStatsPayload> {
  const base = getPhpApiBase();
  if (!base) {
    throw new Error("NEXT_PUBLIC_PHP_API_BASE belum diatur");
  }
  const root = base.replace(/\/$/, "");
  const url = new URL(`${root}/admin_stats_api.php`);
  url.searchParams.set("username", params.username);
  url.searchParams.set("chart_month", params.chartMonth);
  url.searchParams.set("week_start", params.weekStart);

  const res = await fetch(url.toString(), { method: "GET", cache: "no-store" });
  const body = (await res.json()) as AdminStatsPayload & { error?: string; code?: string };
  if (!res.ok) {
    throw new Error(body.error || `HTTP ${res.status}`);
  }
  if (body.error) {
    throw new Error(body.error);
  }
  return body as AdminStatsPayload;
}
