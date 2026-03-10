import { useQuery } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";

export function useCommittees() {
  return useQuery({
    queryKey: [api.committees.list.path],
    queryFn: async () => {
      const res = await fetch(api.committees.list.path);
      if (!res.ok) throw new Error("Failed to fetch committees");
      return api.committees.list.responses[200].parse(await res.json());
    },
  });
}

export function useCommitteeMembers(committeeId: number | null) {
  return useQuery({
    queryKey: [api.committees.members.path, committeeId],
    queryFn: async () => {
      if (!committeeId) return [];
      const url = buildUrl(api.committees.members.path, { id: committeeId });
      const res = await fetch(url);
      if (!res.ok) throw new Error("Failed to fetch members");
      return api.committees.members.responses[200].parse(await res.json());
    },
    enabled: !!committeeId,
  });
}
