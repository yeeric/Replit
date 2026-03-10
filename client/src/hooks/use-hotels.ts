import { useQuery } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";

export function useHotelRooms() {
  return useQuery({
    queryKey: [api.hotelRooms.list.path],
    queryFn: async () => {
      const res = await fetch(api.hotelRooms.list.path);
      if (!res.ok) throw new Error("Failed to fetch hotel rooms");
      return api.hotelRooms.list.responses[200].parse(await res.json());
    },
  });
}

export function useHotelStudents(roomNumber: number | null) {
  return useQuery({
    queryKey: [api.hotelRooms.students.path, roomNumber],
    queryFn: async () => {
      if (!roomNumber) return [];
      const url = buildUrl(api.hotelRooms.students.path, { id: roomNumber });
      const res = await fetch(url);
      if (!res.ok) throw new Error("Failed to fetch students");
      return api.hotelRooms.students.responses[200].parse(await res.json());
    },
    enabled: !!roomNumber,
  });
}
