import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";
import { z } from "zod";

export function useCompanies() {
  return useQuery({
    queryKey: [api.companies.list.path],
    queryFn: async () => {
      const res = await fetch(api.companies.list.path);
      if (!res.ok) throw new Error("Failed to fetch companies");
      return api.companies.list.responses[200].parse(await res.json());
    },
  });
}

export function useSponsors() {
  return useQuery({
    queryKey: [api.sponsors.list.path],
    queryFn: async () => {
      const res = await fetch(api.sponsors.list.path);
      if (!res.ok) throw new Error("Failed to fetch sponsors");
      return api.sponsors.list.responses[200].parse(await res.json());
    },
  });
}

export function useCreateCompany() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: z.infer<typeof api.companies.create.input>) => {
      const res = await fetch(api.companies.create.path, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      });
      if (!res.ok) throw new Error("Failed to create company");
      return res.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.companies.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.sponsors.list.path] });
    },
  });
}

export function useDeleteCompany() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      const url = buildUrl(api.companies.delete.path, { id });
      const res = await fetch(url, { method: "DELETE" });
      if (!res.ok) throw new Error("Failed to delete company");
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [api.companies.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.sponsors.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.attendees.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.jobs.list.path] });
    },
  });
}
