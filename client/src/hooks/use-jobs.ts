import { useQuery } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";

export function useJobs() {
  return useQuery({
    queryKey: [api.jobs.list.path],
    queryFn: async () => {
      const res = await fetch(api.jobs.list.path);
      if (!res.ok) throw new Error("Failed to fetch jobs");
      return api.jobs.list.responses[200].parse(await res.json());
    },
  });
}

export function useCompanyJobs(companyId: number | null) {
  return useQuery({
    queryKey: [api.companies.jobs.path, companyId],
    queryFn: async () => {
      if (!companyId) return [];
      const url = buildUrl(api.companies.jobs.path, { id: companyId });
      const res = await fetch(url);
      if (!res.ok) throw new Error("Failed to fetch company jobs");
      return api.companies.jobs.responses[200].parse(await res.json());
    },
    enabled: !!companyId,
  });
}
