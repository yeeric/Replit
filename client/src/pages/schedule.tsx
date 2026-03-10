import { useState } from "react";
import { Shell } from "@/components/layout/Shell";
import { useSessionDates, useSessions, useUpdateSession } from "@/hooks/use-sessions";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Clock, MapPin, Edit2, Loader2 } from "lucide-react";
import { useForm } from "react-hook-form";
import { format } from "date-fns";

export default function Schedule() {
  const { data: dates, isLoading: isLoadingDates } = useSessionDates();
  const [selectedDate, setSelectedDate] = useState<string | undefined>();
  const { data: sessions, isLoading: isLoadingSessions } = useSessions(selectedDate);
  const updateSession = useUpdateSession();

  // If dates loaded and none selected, select first
  if (dates?.length && !selectedDate) {
    setSelectedDate(dates[0]);
  }

  return (
    <Shell>
      <div className="space-y-8">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Conference Schedule</h1>
            <p className="text-muted-foreground mt-2">Manage sessions and track locations.</p>
          </div>
        </div>

        {isLoadingDates ? (
          <div className="h-10 w-full bg-muted rounded-xl animate-pulse max-w-2xl" />
        ) : dates?.length ? (
          <Tabs value={selectedDate} onValueChange={setSelectedDate} className="w-full">
            <TabsList className="bg-muted/50 p-1 rounded-xl flex flex-wrap h-auto w-fit">
              {dates.map((d: string) => (
                <TabsTrigger 
                  key={d} 
                  value={d}
                  className="rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-sm px-6 py-2.5"
                >
                  {format(new Date(d), "MMMM d, yyyy")}
                </TabsTrigger>
              ))}
            </TabsList>
          </Tabs>
        ) : null}

        <div className="grid gap-4 mt-6">
          {isLoadingSessions ? (
            <div className="p-12 text-center flex items-center justify-center text-muted-foreground">
              <Loader2 className="w-6 h-6 animate-spin mr-2" /> Loading sessions...
            </div>
          ) : sessions?.length ? (
            sessions.map((s: any) => (
              <SessionCard 
                key={s.sessionId || s.sessionid} 
                session={s} 
                onUpdate={(id, data) => updateSession.mutateAsync({ id, updates: data })}
                isPending={updateSession.isPending}
              />
            ))
          ) : (
            <div className="p-12 text-center border-2 border-dashed border-border rounded-2xl text-muted-foreground">
              No sessions scheduled for this date.
            </div>
          )}
        </div>
      </div>
    </Shell>
  );
}

function SessionCard({ session, onUpdate, isPending }: { session: any, onUpdate: any, isPending: boolean }) {
  const [open, setOpen] = useState(false);
  const { register, handleSubmit } = useForm({
    defaultValues: {
      date: session.date ? new Date(session.date).toISOString().split('T')[0] : '',
      startTime: session.startTime || session.starttime,
      endTime: session.endTime || session.endtime,
      roomLocation: session.roomLocation || session.roomlocation,
    }
  });

  const onSubmit = async (data: any) => {
    await onUpdate(session.sessionId || session.sessionid, data);
    setOpen(false);
  };

  return (
    <Card className="subtle-shadow border-none hover:shadow-md transition-all duration-200">
      <CardContent className="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div className="space-y-3 flex-1">
          <h3 className="text-xl font-bold text-foreground">
            {session.sessionName || session.sessionname}
          </h3>
          <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
            <div className="flex items-center gap-1.5 bg-muted/50 px-3 py-1.5 rounded-full">
              <Clock className="w-4 h-4" />
              <span className="font-medium">{session.startTime || session.starttime} - {session.endTime || session.endtime}</span>
            </div>
            <div className="flex items-center gap-1.5 bg-muted/50 px-3 py-1.5 rounded-full">
              <MapPin className="w-4 h-4" />
              <span className="font-medium">{session.roomLocation || session.roomlocation}</span>
            </div>
          </div>
        </div>
        
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button variant="outline" className="rounded-xl px-6 bg-background">
              <Edit2 className="w-4 h-4 mr-2" />
              Edit Details
            </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[425px] rounded-2xl p-6">
            <DialogHeader>
              <DialogTitle className="text-xl">Edit Session</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5 mt-4">
              <div className="space-y-2">
                <Label>Date</Label>
                <Input type="date" {...register("date")} className="rounded-xl h-11" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Start Time</Label>
                  <Input type="time" {...register("startTime")} className="rounded-xl h-11" />
                </div>
                <div className="space-y-2">
                  <Label>End Time</Label>
                  <Input type="time" {...register("endTime")} className="rounded-xl h-11" />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Room Location</Label>
                <Input {...register("roomLocation")} className="rounded-xl h-11" />
              </div>
              <Button type="submit" disabled={isPending} className="w-full h-11 rounded-xl mt-2">
                {isPending ? "Saving..." : "Save Changes"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </CardContent>
    </Card>
  );
}
