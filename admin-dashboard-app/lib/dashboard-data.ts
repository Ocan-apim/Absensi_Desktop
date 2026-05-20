/** Placeholder series — replace with API responses. */

export type DayPoint = { day: number; guru: number; siswa: number };

export const areaAttendanceByDay: DayPoint[] = Array.from(
  { length: 30 },
  (_, i) => ({
    day: i + 1,
    guru: 1200 + Math.round(Math.sin(i / 4) * 400) + i * 40,
    siswa: 1800 + Math.round(Math.cos(i / 3) * 500) + i * 55,
  })
);

export type TimestampPoint = { label: string; count: number };

export const teacherAttendanceBySlot: TimestampPoint[] = [
  { label: "Sen", count: 8 },
  { label: "Sel", count: 10 },
  { label: "Rab", count: 7 },
  { label: "Kam", count: 11 },
  { label: "Jum", count: 9 },
  { label: "Sab", count: 4 },
  { label: "Min", count: 2 },
];

export const studentAttendanceBySlot: TimestampPoint[] = [
  { label: "Sen", count: 9 },
  { label: "Sel", count: 12 },
  { label: "Rab", count: 8 },
  { label: "Kam", count: 11 },
  { label: "Jum", count: 10 },
  { label: "Sab", count: 5 },
  { label: "Min", count: 3 },
];

export const JURUSAN_ORDER = [
  "PM",
  "DKV",
  "MPLB",
  "TJKT",
  "PPLG",
  "PH",
] as const;

export type JurusanCode = (typeof JURUSAN_ORDER)[number];

export const JURUSAN_COLORS: Record<JurusanCode, string> = {
  PM: "#8B4513",
  DKV: "#DC2626",
  MPLB: "#EAB308",
  TJKT: "#7DD3FC",
  PPLG: "#1E3A8A",
  PH: "#22C55E",
};

export type JurusanSlice = { name: JurusanCode; value: number; fill: string };

export const jurusanDistribution: JurusanSlice[] = [
  { name: "PM", value: 18, fill: JURUSAN_COLORS.PM },
  { name: "DKV", value: 22, fill: JURUSAN_COLORS.DKV },
  { name: "MPLB", value: 15, fill: JURUSAN_COLORS.MPLB },
  { name: "TJKT", value: 20, fill: JURUSAN_COLORS.TJKT },
  { name: "PPLG", value: 17, fill: JURUSAN_COLORS.PPLG },
  { name: "PH", value: 8, fill: JURUSAN_COLORS.PH },
];
