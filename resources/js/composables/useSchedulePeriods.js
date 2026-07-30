// Canonical maintenance schedule period source.
//
// Due dates are generated server-side by App\Services\ScheduleOccurrenceGenerator,
// which assigns each machine's day(s)-of-month from its Excel import row order
// (separately per city), shifts around Sundays/holidays, and balances load fairly
// across working days. That generator is the single source of truth — this file
// only reads the resulting `occurrences` attached to each schedule; it must NOT
// re-derive dates from `interval_days` + `next_due_date` math, since the actual
// assigned dates don't follow a fixed step once holiday-shifting/fair-distribution
// has been applied.
//
// Any UI that needs to show "when is this machine due" (alerts, machine picker,
// report period selector, calendar, etc.) MUST use these helpers.

function pickSchedule(schedules) {
  const active = (schedules ?? []).filter(s => s.is_active !== false);
  return active.length > 0 ? active[0] : null;
}

/**
 * Build the list of due-date periods for a given calendar month for a machine,
 * sourced from the schedule's precomputed `occurrences`.
 * @param {Array} schedules - machine.schedules array (each with an `occurrences` array)
 * @param {Date} [referenceDate] - any date within the target month, defaults to today
 * @returns {Array<{ start: Date, due: Date, scheduleId: string, isShifted: boolean, originalDate: Date|null }>}
 */
export function getMonthlyPeriods(schedules, referenceDate = new Date()) {
  const sched = pickSchedule(schedules);
  if (!sched) return [];

  const year = referenceDate.getFullYear();
  const month = referenceDate.getMonth() + 1; // occurrences use 1-indexed period_month

  const occurrences = (sched.occurrences ?? [])
    .filter(o => Number(o.period_year) === year && Number(o.period_month) === month)
    .map(o => ({ ...o, dueDateObj: (() => { const d = new Date(o.due_date); d.setHours(0, 0, 0, 0); return d; })() }))
    .sort((a, b) => a.dueDateObj - b.dueDateObj);

  if (occurrences.length === 0) return [];

  const monthStart = new Date(year, month - 1, 1);
  monthStart.setHours(0, 0, 0, 0);

  return occurrences.map((occ, idx) => {
    const prevDue = idx === 0 ? new Date(monthStart.getTime() - 86400000) : occurrences[idx - 1].dueDateObj;
    const start = new Date(prevDue.getTime() + 86400000);
    return {
      start,
      due: occ.dueDateObj,
      scheduleId: sched.id,
      isShifted: !!occ.is_shifted,
      originalDate: occ.original_date ? new Date(occ.original_date) : null,
    };
  });
}

/**
 * Get the currently-active period: the latest period whose due date is today or
 * in the past (overdue/current), or the first upcoming one if none has passed yet.
 * @param {Array} schedules - machine.schedules array
 * @param {Date} [referenceDate] - defaults to today
 * @returns {{ start: Date, due: Date, scheduleId: string, diffDays: number } | null}
 */
export function getCurrentPeriod(schedules, referenceDate = new Date()) {
  const today = new Date(referenceDate);
  today.setHours(0, 0, 0, 0);

  const periods = getMonthlyPeriods(schedules, today);
  if (periods.length === 0) return null;

  const past = periods.filter(p => p.due <= today);
  const period = past.length > 0 ? past[past.length - 1] : periods[0];
  const diffDays = Math.ceil((period.due - today) / 86400000);
  return { ...period, diffDays };
}

/**
 * Get the nearest upcoming occurrence strictly after the reference date, looking
 * across whatever months are currently loaded on the schedule (typically the
 * current + next month, per the backend's 2-month generation window). Used to
 * show "next due date" once the current period is already fully completed.
 * @param {Array} schedules - machine.schedules array
 * @param {Date} [referenceDate] - defaults to today
 * @returns {{ due: Date, scheduleId: string } | null}
 */
export function getNextUpcomingPeriod(schedules, referenceDate = new Date()) {
  const sched = pickSchedule(schedules);
  if (!sched) return null;

  const today = new Date(referenceDate);
  today.setHours(0, 0, 0, 0);

  const upcoming = (sched.occurrences ?? [])
    .map(o => { const d = new Date(o.due_date); d.setHours(0, 0, 0, 0); return d; })
    .filter(d => d > today)
    .sort((a, b) => a - b);

  return upcoming.length > 0 ? { due: upcoming[0], scheduleId: sched.id } : null;
}
