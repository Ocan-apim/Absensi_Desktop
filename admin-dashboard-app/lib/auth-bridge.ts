const AUTH_KEY = "absensi_auth";

export function requestAuthFromParent(): void {
  if (typeof window === "undefined") return;
  if (window.parent === window) return;
  window.parent.postMessage(
    { type: "absensi_request_auth" },
    window.location.origin
  );
}

/** Parent (admin-dashboard.html) sends session — iframe sessionStorage is separate from parent. */
export function installParentAuthBridge(onStored: () => void): () => void {
  function handler(e: MessageEvent) {
    if (e.origin !== window.location.origin) return;
    const d = e.data as { type?: string; payload?: unknown } | null;
    if (!d || d.type !== "absensi_auth" || !d.payload) return;
    try {
      sessionStorage.setItem(AUTH_KEY, JSON.stringify(d.payload));
      onStored();
    } catch {
      /* quota / private mode */
    }
  }
  window.addEventListener("message", handler);
  return () => window.removeEventListener("message", handler);
}
