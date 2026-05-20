"use client";

import { useMemo } from "react";

const WEEKDAY_LABELS = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];

function startMondayOffset(d: Date) {
  const day = d.getDay();
  return (day + 6) % 7;
}

function buildMonthGrid(year: number, monthIndex: number) {
  const first = new Date(year, monthIndex, 1);
  const last = new Date(year, monthIndex + 1, 0);
  const pad = startMondayOffset(first);
  const daysInMonth = last.getDate();
  const cells: (number | null)[] = [];
  for (let i = 0; i < pad; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);
  while (cells.length % 7 !== 0) cells.push(null);
  return cells;
}

function sameDay(a: Date, b: Date) {
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  );
}

type WeekCalendarProps = {
  visible: boolean;
  viewMonth: Date;
  selected: Date;
  onPrevMonth: () => void;
  onNextMonth: () => void;
  onSelectDay: (day: number) => void;
};

export function WeekCalendar({
  visible,
  viewMonth,
  selected,
  onPrevMonth,
  onNextMonth,
  onSelectDay,
}: WeekCalendarProps) {
  const grid = useMemo(
    () => buildMonthGrid(viewMonth.getFullYear(), viewMonth.getMonth()),
    [viewMonth]
  );

  if (!visible) return null;

  const title = viewMonth.toLocaleString("id-ID", {
    month: "long",
    year: "numeric",
  });

  return (
    <div
      className="absolute left-0 top-full z-20 mt-2 w-[min(100vw-2rem,320px)] rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-[#1E1E21]"
      role="dialog"
      aria-label="Pilih tanggal"
    >
      <div className="mb-3 flex items-center justify-between">
        <p className="text-sm font-semibold capitalize text-zinc-900 dark:text-zinc-50">
          {title}
        </p>
        <div className="flex gap-1">
          <button
            type="button"
            onClick={onPrevMonth}
            className="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="Bulan sebelumnya"
          >
            ‹
          </button>
          <button
            type="button"
            onClick={onNextMonth}
            className="flex h-8 w-8 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
            aria-label="Bulan berikutnya"
          >
            ›
          </button>
        </div>
      </div>
      <div className="mb-2 grid grid-cols-7 gap-1 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400">
        {WEEKDAY_LABELS.map((d) => (
          <span key={d}>{d}</span>
        ))}
      </div>
      <div className="grid grid-cols-7 gap-1 text-center text-sm">
        {grid.map((cell, idx) => {
          if (cell === null) {
            return <span key={`e-${idx}`} className="h-9" />;
          }
          const date = new Date(
            viewMonth.getFullYear(),
            viewMonth.getMonth(),
            cell
          );
          const isSelected = sameDay(date, selected);
          return (
            <button
              key={cell}
              type="button"
              onClick={() => onSelectDay(cell)}
              className={`flex h-9 w-9 items-center justify-center justify-self-center rounded-full text-zinc-800 transition dark:text-zinc-100 ${
                isSelected
                  ? "bg-red-500 text-white shadow-sm dark:bg-[#6B9080]"
                  : "hover:bg-zinc-100 dark:hover:bg-zinc-800"
              }`}
            >
              {cell}
            </button>
          );
        })}
      </div>
    </div>
  );
}
