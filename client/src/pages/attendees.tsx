import { useState } from "react";
import { Shell } from "@/components/layout/Shell";
import { useAttendees, useCreateAttendee } from "@/hooks/use-attendees";
import { useCompanies } from "@/hooks/use-companies";
import { Card, CardContent } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useForm, Controller } from "react-hook-form";
import { Plus, GraduationCap, Briefcase, Building2, UserPlus } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function Attendees() {
  const { data: attendeesData, isLoading } = useAttendees();
  const createAttendee = useCreateAttendee();
  const { data: companies } = useCompanies();
  const [open, setOpen] = useState(false);
  const { toast } = useToast();
  
  const { register, handleSubmit, watch, control, reset, formState: { errors } } = useForm({
    defaultValues: {
      firstName: "", lastName: "", email: "", attendeeType: "Student",
      roomNumberStaysIn: "", companyId: "", sponsorLevel: "Bronze"
    }
  });

  const attendeeType = watch("attendeeType");

  const onSubmit = async (data: any) => {
    try {
      await createAttendee.mutateAsync(data);
      toast({ title: "Success", description: "Attendee added successfully." });
      reset();
      setOpen(false);
    } catch (err: any) {
      toast({ variant: "destructive", title: "Error", description: err.message });
    }
  };

  const tabs = [
    { id: "students", label: "Students", icon: GraduationCap, data: attendeesData?.students || [] },
    { id: "professionals", label: "Professionals", icon: Briefcase, data: attendeesData?.professionals || [] },
    { id: "sponsors", label: "Sponsors", icon: Building2, data: attendeesData?.sponsors || [] },
  ];

  return (
    <Shell>
      <div className="space-y-8">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Conference Attendees</h1>
            <p className="text-muted-foreground mt-2">Manage registrations and profiles.</p>
          </div>

          <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
              <Button className="rounded-xl h-11 px-6 shadow-md">
                <UserPlus className="w-4 h-4 mr-2" /> Add Attendee
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[500px] rounded-2xl max-h-[90vh] overflow-y-auto">
              <DialogHeader>
                <DialogTitle>Register New Attendee</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-5 mt-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>First Name</Label>
                    <Input {...register("firstName")} className="h-11 rounded-xl" />
                  </div>
                  <div className="space-y-2">
                    <Label>Last Name</Label>
                    <Input {...register("lastName")} className="h-11 rounded-xl" />
                  </div>
                </div>
                
                <div className="space-y-2">
                  <Label>Email Address</Label>
                  <Input type="email" {...register("email")} className="h-11 rounded-xl" />
                </div>

                <div className="space-y-2">
                  <Label>Attendee Type</Label>
                  <Controller
                    name="attendeeType"
                    control={control}
                    render={({ field }) => (
                      <Select onValueChange={field.onChange} defaultValue={field.value}>
                        <SelectTrigger className="h-11 rounded-xl">
                          <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Student">Student</SelectItem>
                          <SelectItem value="Professional">Professional</SelectItem>
                          <SelectItem value="Sponsor">Sponsor</SelectItem>
                        </SelectContent>
                      </Select>
                    )}
                  />
                </div>

                {attendeeType === "Student" && (
                  <div className="space-y-2 animate-in fade-in slide-in-from-top-2">
                    <Label>Hotel Room Number (Optional)</Label>
                    <Input type="number" {...register("roomNumberStaysIn")} className="h-11 rounded-xl" />
                  </div>
                )}

                {attendeeType === "Sponsor" && (
                  <div className="space-y-4 animate-in fade-in slide-in-from-top-2 bg-muted/30 p-4 rounded-xl border border-border">
                    <div className="space-y-2">
                      <Label>Company</Label>
                      <Controller
                        name="companyId"
                        control={control}
                        render={({ field }) => (
                          <Select onValueChange={field.onChange} value={field.value}>
                            <SelectTrigger className="h-11 rounded-xl bg-background">
                              <SelectValue placeholder="Select company" />
                            </SelectTrigger>
                            <SelectContent>
                              {companies?.map((c: any) => (
                                <SelectItem key={c.companyId || c.companyid} value={String(c.companyId || c.companyid)}>
                                  {c.companyName || c.companyname}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                        )}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label>Sponsorship Level</Label>
                      <Controller
                        name="sponsorLevel"
                        control={control}
                        render={({ field }) => (
                          <Select onValueChange={field.onChange} defaultValue={field.value}>
                            <SelectTrigger className="h-11 rounded-xl bg-background">
                              <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="Platinum">Platinum</SelectItem>
                              <SelectItem value="Gold">Gold</SelectItem>
                              <SelectItem value="Silver">Silver</SelectItem>
                              <SelectItem value="Bronze">Bronze</SelectItem>
                            </SelectContent>
                          </Select>
                        )}
                      />
                    </div>
                  </div>
                )}

                <Button type="submit" disabled={createAttendee.isPending} className="w-full h-11 rounded-xl mt-4">
                  {createAttendee.isPending ? "Processing..." : "Complete Registration"}
                </Button>
              </form>
            </DialogContent>
          </Dialog>
        </div>

        <Tabs defaultValue="students" className="w-full">
          <TabsList className="bg-muted/50 p-1 rounded-xl grid w-full grid-cols-3 mb-6 max-w-2xl">
            {tabs.map(tab => (
              <TabsTrigger 
                key={tab.id} 
                value={tab.id}
                className="rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-sm py-2.5 font-medium flex items-center justify-center gap-2"
              >
                <tab.icon className="w-4 h-4" />
                {tab.label}
              </TabsTrigger>
            ))}
          </TabsList>

          {tabs.map(tab => (
            <TabsContent key={tab.id} value={tab.id}>
              <Card className="subtle-shadow border-none overflow-hidden rounded-2xl">
                {isLoading ? (
                  <div className="p-12 text-center text-muted-foreground">Loading {tab.label.toLowerCase()}...</div>
                ) : tab.data.length ? (
                  <div className="overflow-x-auto">
                    <Table>
                      <TableHeader className="bg-muted/30">
                        <TableRow>
                          <TableHead className="w-[80px]">ID</TableHead>
                          <TableHead>Name</TableHead>
                          <TableHead>Email</TableHead>
                          {tab.id === 'students' && <TableHead>Room</TableHead>}
                          {tab.id === 'sponsors' && <TableHead>Company & Level</TableHead>}
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {tab.data.map((p: any) => (
                          <TableRow key={p.attendeeId || p.attendeeid} className="hover:bg-muted/30">
                            <TableCell className="font-mono text-muted-foreground text-xs">
                              #{p.attendeeId || p.attendeeid}
                            </TableCell>
                            <TableCell className="font-medium">
                              {p.firstName || p.firstname} {p.lastName || p.lastname}
                            </TableCell>
                            <TableCell className="text-muted-foreground">{p.email}</TableCell>
                            
                            {tab.id === 'students' && (
                              <TableCell>
                                {p.roomNumberStaysIn || p.roomnumberstaysin ? (
                                  <span className="bg-secondary px-2 py-1 rounded-md text-xs font-medium">
                                    {p.roomNumberStaysIn || p.roomnumberstaysin}
                                  </span>
                                ) : <span className="text-muted-foreground italic text-sm">Unassigned</span>}
                              </TableCell>
                            )}
                            
                            {tab.id === 'sponsors' && (
                              <TableCell>
                                <div className="flex flex-col">
                                  <span className="font-medium">{p.companyName || p.companyname}</span>
                                  <span className="text-xs text-muted-foreground">{p.sponsorLevel || p.sponsorlevel}</span>
                                </div>
                              </TableCell>
                            )}
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                ) : (
                  <div className="p-16 text-center text-muted-foreground flex flex-col items-center justify-center">
                    <tab.icon className="w-12 h-12 mb-4 opacity-20" />
                    <p>No {tab.label.toLowerCase()} registered yet.</p>
                  </div>
                )}
              </Card>
            </TabsContent>
          ))}
        </Tabs>
      </div>
    </Shell>
  );
}
