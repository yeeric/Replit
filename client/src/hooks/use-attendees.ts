import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api } from "@shared/routes";
import { z } from "zod";

export function useAttendees() {
  return useQuery({
    queryKey: [api.attendees.list.path],
    queryFn: async () => {
      const res = await fetch(api.attendees.list.path);
      if (!res.ok) throw new Error("Failed to fetch attendees");
      return api.attendees.list.responses[200].parse(await res.json());
    },
  });
}

export function useCreateAttendee() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: z.infer<typeof api.attendees.create.input>) => {
      const res = await fetch(api.attendees.create.path, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      if (!res.ok) {
        const err = await res.json();
        throw new Error(err.message || "Failed to create attendee");
      }
      return res.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.attendees.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.stats.intake.path] });
    },
  });
}
