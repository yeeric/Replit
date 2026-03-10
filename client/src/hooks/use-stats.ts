import { useQuery } from "@tanstack/react-query";
import { api } from "@shared/routes";

export function useStats() {
  return useQuery({
    queryKey: [api.stats.intake.path],
    queryFn: async () => {
      const res = await fetch(api.stats.intake.path);
      if (!res.ok) throw new Error("Failed to fetch stats");
      return api.stats.intake.responses[200].parse(await res.json());
    },
  });
}
