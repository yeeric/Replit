import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";
import { z } from "zod";

export function useSessionDates() {
  return useQuery({
    queryKey: [api.sessions.dates.path],
    queryFn: async () => {
      const res = await fetch(api.sessions.dates.path);
      if (!res.ok) throw new Error("Failed to fetch dates");
      return api.sessions.dates.responses[200].parse(await res.json());
    },
  });
}

export function useSessions(date?: string) {
  return useQuery({
    queryKey: [api.sessions.list.path, date],
    queryFn: async () => {
      const url = new URL(api.sessions.list.path, window.location.origin);
      if (date) url.searchParams.set("date", date);
      const res = await fetch(url.toString());
      if (!res.ok) throw new Error("Failed to fetch sessions");
      return api.sessions.list.responses[200].parse(await res.json());
    },
  });
}

export function useUpdateSession() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, updates }: { id: number; updates: z.infer<typeof api.sessions.update.input> }) => {
      const url = buildUrl(api.sessions.update.path, { id });
      const res = await fetch(url, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updates),
      });
      if (!res.ok) throw new Error("Failed to update session");
      return res.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.sessions.list.path] });
    },
  });
}
