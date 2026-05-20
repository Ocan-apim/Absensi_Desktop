"use client";

import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from "react";
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { WeekCalendar } from "./WeekCalendar";
import {
  installParentAuthBridge,
  requestAuthFromParent,
} from "@/lib/auth-bridge";
import {
  fetchAdminStats,
  formatChartMonth,
  formatDateIso,
  getAdminStatsUsername,
  getPhpApiBase,
  jurusanToPieSlices,
  type AdminStatsMeta,
} from "@/lib/admin-stats";
import {
  areaAttendanceByDay,
  JURUSAN_ORDER,
  JURUSAN_COLORS,
  jurusanDistribution,
  studentAttendanceBySlot,
  teacherAttendanceBySlot,
  type DayPoint,
  type JurusanCode,
  type JurusanSlice,
  type TimestampPoint,
} from "@/lib/dashboard-data";

const GURU_STROKE = "#5B21B6";
const GURU_FILL = "#7C3AED";
const SISWA_STROKE = "#CA8A04";
const SISWA_FILL = "#FACC15";

function Card({
  children,
  className = "",
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={`rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 ${className}`}
    >
      {children}
    </div>
  );
}

export function AdminDataDashboard() {
  const [calendarOpen, setCalendarOpen] = useState(false);
  const [selectedDate, setSelectedDate] = useState(() => new Date());
  const [viewMonth, setViewMonth] = useState(() => new Date());
  const wrapRef = useRef<HTMLDivElement>(null);

  const toggleCalendar = useCallback(() => {
    setCalendarOpen((o) => !o);
  }, []);

  const onPrevMonth = useCallback(() => {
    setViewMonth((d) => new Date(d.getFullYear(), d.getMonth() - 1, 1));
  }, []);

  const onNextMonth = useCallback(() => {
    setViewMonth((d) => new Date(d.getFullYear(), d.getMonth() + 1, 1));
  }, []);

  const onSelectDay = useCallback((day: number) => {
    setSelectedDate(
      new Date(viewMonth.getFullYear(), viewMonth.getMonth(), day)
    );
  }, [viewMonth]);

  useEffect(() => {
    function onDocMouseDown(e: MouseEvent) {
      if (!calendarOpen) return;
      const el = wrapRef.current;
      if (el && !el.contains(e.target as Node)) {
        setCalendarOpen(false);
      }
    }
    document.addEventListener("mousedown", onDocMouseDown);
    return () => document.removeEventListener("mousedown", onDocMouseDown);
  }, [calendarOpen]);

  const rangeLabel = useMemo(() => {
    const start = new Date(selectedDate);
    const day = start.getDay();
    const diff = (day + 6) % 7;
    start.setDate(start.getDate() - diff);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    const fmt = (d: Date) =>
      d.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
    return `${fmt(start)} – ${fmt(end)}`;
  }, [selectedDate]);

  const [liveStats, setLiveStats] = useState<{
    meta: AdminStatsMeta;
    areaByDay: DayPoint[];
    weekGuru: TimestampPoint[];
    weekSiswa: TimestampPoint[];
    jurusanRows: { name: JurusanCode; count: number; value: number }[];
  } | null>(null);
  const [statsError, setStatsError] = useState<string | null>(null);
  const [statsLoading, setStatsLoading] = useState(false);
  const [parentAuthEpoch, setParentAuthEpoch] = useState(0);

  useEffect(() => {
    requestAuthFromParent();
    return installParentAuthBridge(() => {
      setParentAuthEpoch((n) => n + 1);
    });
  }, []);

  useEffect(() => {
    let cancelled = false;
    const base = getPhpApiBase();
    const user = getAdminStatsUsername();
    if (!base || !user) {
      setLiveStats(null);
      setStatsLoading(false);
      setStatsError(
        !base
          ? "Set NEXT_PUBLIC_PHP_API_BASE ke URL folder Absensi PHP (mis. http://absensi.test/Absensi)."
          : "Tidak ada username admin: login lewat portal yang sama atau set NEXT_PUBLIC_ADMIN_STATS_USERNAME."
      );
      return;
    }

    const chartMonth = formatChartMonth(viewMonth);
    const weekStart = formatDateIso(selectedDate);

    setStatsLoading(true);
    setStatsError(null);

    fetchAdminStats({ username: user, chartMonth, weekStart })
      .then((payload) => {
        if (cancelled) return;
        setLiveStats({
          meta: payload.meta,
          areaByDay: payload.areaByDay,
          weekGuru: payload.weekBars.guru,
          weekSiswa: payload.weekBars.siswa,
          jurusanRows: payload.jurusan as {
            name: JurusanCode;
            count: number;
            value: number;
          }[],
        });
      })
      .catch((e: unknown) => {
        if (cancelled) return;
        setLiveStats(null);
        setStatsError(e instanceof Error ? e.message : "Gagal memuat statistik");
      })
      .finally(() => {
        if (!cancelled) setStatsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [viewMonth, selectedDate, parentAuthEpoch]);

  const areaChartData: DayPoint[] = liveStats?.areaByDay ?? areaAttendanceByDay;
  const teacherBars: TimestampPoint[] =
    liveStats?.weekGuru ?? teacherAttendanceBySlot;
  const studentBars: TimestampPoint[] =
    liveStats?.weekSiswa ?? studentAttendanceBySlot;
  const pieSlices: JurusanSlice[] = liveStats
    ? jurusanToPieSlices(liveStats.jurusanRows)
    : jurusanDistribution;

  return (
    <div className="min-h-full bg-[#f8f9fa] px-4 py-8 font-sans text-zinc-900 dark:bg-zinc-950 dark:text-zinc-50 md:px-8">
      <div className="mx-auto max-w-6xl space-y-6">
        <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h1 className="text-2xl font-semibold tracking-tight">
              Data Dashboard
            </h1>
            <p className="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
              Kehadiran guru vs siswa dan distribusi jurusan
            </p>
          </div>
          <div ref={wrapRef} className="relative flex flex-wrap items-center gap-3">
            <button
              type="button"
              onClick={toggleCalendar}
              className="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-zinc-800 shadow-sm transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800"
              aria-expanded={calendarOpen}
            >
              <svg
                className="h-4 w-4 text-zinc-500"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                aria-hidden
              >
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <path d="M16 2v4M8 2v4M3 10h18" />
              </svg>
              This week
              <span className="text-zinc-400">· {rangeLabel}</span>
              <svg
                className={`h-4 w-4 text-zinc-400 transition ${calendarOpen ? "rotate-180" : ""}`}
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                aria-hidden
              >
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>
            <WeekCalendar
              visible={calendarOpen}
              viewMonth={viewMonth}
              selected={selectedDate}
              onPrevMonth={onPrevMonth}
              onNextMonth={onNextMonth}
              onSelectDay={onSelectDay}
            />
          </div>
        </header>

        {statsError ? (
          <div
            className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
            role="status"
          >
            {statsError}{" "}
            <span className="text-amber-800/90 dark:text-amber-200/90">
              Menampilkan data contoh hingga koneksi berhasil.
            </span>
          </div>
        ) : null}
        {statsLoading ? (
          <p className="text-sm text-zinc-500 dark:text-zinc-400">Memuat data…</p>
        ) : null}

        <Card>
          <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="flex flex-wrap items-center gap-4 text-sm">
              <span className="inline-flex items-center gap-2 font-medium text-zinc-800 dark:text-zinc-100">
                <span
                  className="h-3 w-3 rounded-full ring-2 ring-white dark:ring-zinc-900"
                  style={{ background: GURU_FILL }}
                  aria-hidden
                />
                Guru
              </span>
              <span className="inline-flex items-center gap-2 font-medium text-zinc-800 dark:text-zinc-100">
                <span
                  className="h-3 w-3 rounded-full ring-2 ring-white dark:ring-zinc-900"
                  style={{ background: SISWA_FILL }}
                  aria-hidden
                />
                Siswa
              </span>
            </div>
            <p className="text-sm text-zinc-500 dark:text-zinc-400 sm:max-w-md sm:text-right">
              {liveStats
                ? `Bulan ${liveStats.meta.chart_month} — jumlah entri hadir per hari kalender.`
                : "Kehadiran guru vs siswa — sumbu bawah hari dalam bulan, sumbu samping jumlah kehadiran (contoh)."}
            </p>
          </div>
          <div className="h-[320px] w-full min-w-0 min-h-[280px]">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart
                data={areaChartData}
                margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
              >
                <defs>
                  <linearGradient id="fillGuru" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={GURU_FILL} stopOpacity={0.45} />
                    <stop offset="100%" stopColor="#A78BFA" stopOpacity={0.05} />
                  </linearGradient>
                  <linearGradient id="fillSiswa" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor={SISWA_FILL} stopOpacity={0.5} />
                    <stop offset="100%" stopColor="#FDE047" stopOpacity={0.06} />
                  </linearGradient>
                </defs>
                <CartesianGrid
                  strokeDasharray="3 3"
                  className="stroke-zinc-200 dark:stroke-zinc-700"
                  vertical={false}
                />
                <XAxis
                  dataKey="day"
                  tick={{ fontSize: 11, fill: "currentColor" }}
                  className="text-zinc-500"
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis
                  tick={{ fontSize: 11, fill: "currentColor" }}
                  className="text-zinc-500"
                  axisLine={false}
                  tickLine={false}
                />
                <Tooltip
                  contentStyle={{
                    borderRadius: 12,
                    border: "1px solid #e4e4e7",
                    fontSize: 12,
                  }}
                  labelFormatter={(v) => `Hari ke-${v}`}
                />
                <Area
                  type="monotone"
                  dataKey="guru"
                  name="Guru"
                  stroke={GURU_STROKE}
                  strokeWidth={2}
                  fill="url(#fillGuru)"
                />
                <Area
                  type="monotone"
                  dataKey="siswa"
                  name="Siswa"
                  stroke={SISWA_STROKE}
                  strokeWidth={2}
                  fill="url(#fillSiswa)"
                />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </Card>

        <div className="grid gap-6 lg:grid-cols-12 lg:items-stretch">
          <Card className="lg:col-span-4 flex flex-col">
            <p className="mb-3 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
              Siswa per jurusan
            </p>
            <ul className="mb-4 flex flex-col gap-2">
              {JURUSAN_ORDER.map((code: JurusanCode) => {
                const row = liveStats?.jurusanRows.find((j) => j.name === code);
                const pct =
                  row?.value ??
                  jurusanDistribution.find((j) => j.name === code)?.value ??
                  0;
                return (
                  <li
                    key={code}
                    className="flex items-center justify-between text-sm"
                  >
                    <span className="inline-flex items-center gap-2 font-medium text-zinc-800 dark:text-zinc-100">
                      <span
                        className="h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-zinc-300/80 dark:ring-zinc-600"
                        style={{ background: JURUSAN_COLORS[code] }}
                      />
                      {code}
                    </span>
                    <span className="tabular-nums text-zinc-500 dark:text-zinc-400">
                      {pct}%
                    </span>
                  </li>
                );
              })}
            </ul>
            <div className="relative mx-auto aspect-square w-full max-w-[240px] min-h-[220px] min-w-0 flex-1">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={pieSlices}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    innerRadius={52}
                    outerRadius={80}
                    paddingAngle={2}
                    label={({ percent }) =>
                      `${((percent ?? 0) * 100).toFixed(0)}%`
                    }
                  >
                    {pieSlices.map((entry) => (
                      <Cell key={entry.name} fill={entry.fill} stroke="none" />
                    ))}
                  </Pie>
                  <Tooltip
                    formatter={(value, name) => [
                      `${value ?? 0}%`,
                      String(name),
                    ]}
                  />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </Card>

          <div className="lg:col-span-8 grid gap-6 md:grid-cols-2">
            <Card>
              <p className="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                Guru — per periode (minggu)
              </p>
              <div className="h-[260px] w-full min-w-0 min-h-[240px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={teacherBars}
                    margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
                  >
                    <CartesianGrid
                      strokeDasharray="3 3"
                      className="stroke-zinc-200 dark:stroke-zinc-700"
                      vertical={false}
                    />
                    <XAxis
                      dataKey="label"
                      tick={{ fontSize: 11 }}
                      axisLine={false}
                      tickLine={false}
                    />
                    <YAxis
                      tick={{ fontSize: 11 }}
                      axisLine={false}
                      tickLine={false}
                      allowDecimals={false}
                    />
                    <Tooltip />
                    <Bar
                      dataKey="count"
                      name="Kehadiran"
                      fill="#6366F1"
                      radius={[6, 6, 0, 0]}
                    />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </Card>
            <Card>
              <p className="mb-4 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                Murid — per periode (minggu)
              </p>
              <div className="h-[260px] w-full min-w-0 min-h-[240px]">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart
                    data={studentBars}
                    margin={{ top: 8, right: 8, left: 0, bottom: 0 }}
                  >
                    <CartesianGrid
                      strokeDasharray="3 3"
                      className="stroke-zinc-200 dark:stroke-zinc-700"
                      vertical={false}
                    />
                    <XAxis
                      dataKey="label"
                      tick={{ fontSize: 11 }}
                      axisLine={false}
                      tickLine={false}
                    />
                    <YAxis
                      tick={{ fontSize: 11 }}
                      axisLine={false}
                      tickLine={false}
                      allowDecimals={false}
                    />
                    <Tooltip />
                    <Bar
                      dataKey="count"
                      name="Kehadiran"
                      fill="#F59E0B"
                      radius={[6, 6, 0, 0]}
                    />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
}
