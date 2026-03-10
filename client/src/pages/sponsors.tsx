import { useState } from "react";
import { Shell } from "@/components/layout/Shell";
import { useCompanies, useSponsors, useCreateCompany, useDeleteCompany } from "@/hooks/use-companies";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Trash2, Building2, Plus, Star } from "lucide-react";
import { useForm } from "react-hook-form";
import { Badge } from "@/components/ui/badge";

export default function Sponsors() {
  const { data: sponsors, isLoading: isLoadingSponsors } = useSponsors();
  const { data: companies, isLoading: isLoadingCompanies } = useCompanies();
  const createCompany = useCreateCompany();
  const deleteCompany = useDeleteCompany();
  
  const [openAdd, setOpenAdd] = useState(false);
  const { register, handleSubmit, reset } = useForm();

  const onSubmit = async (data: any) => {
    await createCompany.mutateAsync(data);
    reset();
    setOpenAdd(false);
  };

  const getSponsorColor = (level: string) => {
    switch (level?.toLowerCase()) {
      case 'platinum': return 'bg-slate-800 text-slate-100 hover:bg-slate-800/80';
      case 'gold': return 'bg-yellow-500/10 text-yellow-700 hover:bg-yellow-500/20 border-yellow-500/20 border';
      case 'silver': return 'bg-slate-200 text-slate-700 hover:bg-slate-300';
      case 'bronze': return 'bg-amber-700/10 text-amber-800 hover:bg-amber-700/20';
      default: return 'bg-muted text-muted-foreground';
    }
  };

  return (
    <Shell>
      <div className="space-y-8">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Sponsors & Companies</h1>
            <p className="text-muted-foreground mt-2">Manage partner organizations.</p>
          </div>
          
          <Dialog open={openAdd} onOpenChange={setOpenAdd}>
            <DialogTrigger asChild>
              <Button className="rounded-xl h-11 px-6 shadow-md">
                <Plus className="w-4 h-4 mr-2" /> Add Company
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[400px] rounded-2xl">
              <DialogHeader>
                <DialogTitle>Register New Company</DialogTitle>
              </DialogHeader>
              <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 mt-4">
                <div className="space-y-2">
                  <Label>Company Name</Label>
                  <Input {...register("companyName", { required: true })} placeholder="Acme Corp" className="h-11 rounded-xl" />
                </div>
                <Button type="submit" disabled={createCompany.isPending} className="w-full h-11 rounded-xl">
                  {createCompany.isPending ? "Creating..." : "Create Company"}
                </Button>
              </form>
            </DialogContent>
          </Dialog>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Sponsors List */}
          <Card className="subtle-shadow border-none rounded-2xl overflow-hidden flex flex-col h-[600px]">
            <div className="p-6 border-b border-border/50 bg-card/50">
              <CardTitle className="flex items-center gap-2">
                <Star className="w-5 h-5 text-yellow-500 fill-yellow-500" /> 
                Active Sponsors
              </CardTitle>
            </div>
            <div className="overflow-y-auto flex-1 p-2">
              {isLoadingSponsors ? (
                <div className="p-8 text-center text-muted-foreground">Loading sponsors...</div>
              ) : sponsors?.length ? (
                <div className="space-y-2">
                  {sponsors.map((s: any, idx: number) => (
                    <div key={idx} className="flex items-center justify-between p-4 rounded-xl hover:bg-muted/50 transition-colors">
                      <div className="font-semibold text-foreground">{s.companyName || s.companyname}</div>
                      <Badge className={`rounded-full px-3 py-1 font-semibold ${getSponsorColor(s.sponsorLevel || s.sponsorlevel)}`}>
                        {s.sponsorLevel || s.sponsorlevel}
                      </Badge>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-12 text-center text-muted-foreground">No sponsors found.</div>
              )}
            </div>
          </Card>

          {/* Companies Management */}
          <Card className="subtle-shadow border-none rounded-2xl overflow-hidden flex flex-col h-[600px]">
            <div className="p-6 border-b border-border/50 bg-card/50">
              <CardTitle className="flex items-center gap-2">
                <Building2 className="w-5 h-5 text-muted-foreground" /> 
                All Registered Companies
              </CardTitle>
            </div>
            <div className="overflow-y-auto flex-1 p-2">
              {isLoadingCompanies ? (
                <div className="p-8 text-center text-muted-foreground">Loading companies...</div>
              ) : companies?.length ? (
                <div className="space-y-2">
                  {companies.map((c: any) => (
                    <div key={c.companyId || c.companyid} className="flex items-center justify-between p-4 rounded-xl hover:bg-muted/50 transition-colors group">
                      <div className="font-medium">{c.companyName || c.companyname}</div>
                      <Button 
                        variant="ghost" 
                        size="icon" 
                        className="opacity-0 group-hover:opacity-100 text-destructive hover:text-destructive hover:bg-destructive/10 transition-all rounded-lg"
                        onClick={() => {
                          if (confirm(`Delete ${c.companyName || c.companyname} and all associated attendees?`)) {
                            deleteCompany.mutate(c.companyId || c.companyid);
                          }
                        }}
                        disabled={deleteCompany.isPending}
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-12 text-center text-muted-foreground">No companies registered.</div>
              )}
            </div>
          </Card>
        </div>
      </div>
    </Shell>
  );
}
