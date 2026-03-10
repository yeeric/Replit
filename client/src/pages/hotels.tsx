import { useState } from "react";
import { Shell } from "@/components/layout/Shell";
import { useHotelRooms, useHotelStudents } from "@/hooks/use-hotels";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card } from "@/components/ui/card";
import { BedDouble, Users } from "lucide-react";

export default function Hotels() {
  const { data: rooms, isLoading: isLoadingRooms } = useHotelRooms();
  const [selectedRoom, setSelectedRoom] = useState<number | null>(null);
  const { data: students, isLoading: isLoadingStudents } = useHotelStudents(selectedRoom);

  return (
    <Shell>
      <div className="space-y-8">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Hotel Assignments</h1>
          <p className="text-muted-foreground mt-2">View student placements in hotel rooms.</p>
        </div>

        <div className="max-w-md">
          <Select 
            onValueChange={(v) => setSelectedRoom(Number(v))} 
            disabled={isLoadingRooms}
          >
            <SelectTrigger className="bg-card subtle-shadow h-12 rounded-xl border-none">
              <SelectValue placeholder="Select a room number..." />
            </SelectTrigger>
            <SelectContent>
              {rooms?.map((r: any) => (
                <SelectItem key={r.roomNumber || r.roomnumber} value={String(r.roomNumber || r.roomnumber)}>
                  Room {r.roomNumber || r.roomnumber} ({r.numberOfBeds || r.numberofbeds} beds)
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {selectedRoom ? (
          <Card className="subtle-shadow border-none overflow-hidden rounded-2xl">
            <div className="p-6 border-b border-border/50 bg-card/50 flex justify-between items-center">
              <h3 className="font-semibold text-lg flex items-center gap-2">
                <Users className="w-5 h-5 text-muted-foreground" />
                Students in Room {selectedRoom}
              </h3>
              <div className="flex items-center gap-2 text-sm text-muted-foreground bg-muted px-3 py-1 rounded-full">
                <BedDouble className="w-4 h-4" />
                Capacity Check
              </div>
            </div>
            
            {isLoadingStudents ? (
              <div className="p-8 text-center text-muted-foreground">Loading occupants...</div>
            ) : students?.length ? (
              <Table>
                <TableHeader className="bg-muted/50">
                  <TableRow>
                    <TableHead>First Name</TableHead>
                    <TableHead>Last Name</TableHead>
                    <TableHead>Email</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {students.map((s: any) => (
                    <TableRow key={s.attendeeId || s.attendeeid} className="hover:bg-muted/30">
                      <TableCell className="font-medium">{s.firstName || s.firstname}</TableCell>
                      <TableCell className="font-medium">{s.lastName || s.lastname}</TableCell>
                      <TableCell className="text-muted-foreground">{s.email}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            ) : (
              <div className="p-12 text-center text-muted-foreground flex flex-col items-center">
                <BedDouble className="w-12 h-12 mb-4 opacity-20" />
                <p>This room is currently empty.</p>
              </div>
            )}
          </Card>
        ) : (
          <div className="h-64 border-2 border-dashed border-border rounded-2xl flex items-center justify-center text-muted-foreground">
            Please select a room to view assigned students.
          </div>
        )}
      </div>
    </Shell>
  );
}
