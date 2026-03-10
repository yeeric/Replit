import { useState } from "react";
import { Shell } from "@/components/layout/Shell";
import { useJobs, useCompanyJobs } from "@/hooks/use-jobs";
import { useCompanies } from "@/hooks/use-companies";
import { Card, CardContent } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Briefcase, MapPin, DollarSign, Building } from "lucide-react";

export default function Jobs() {
  const { data: allJobs, isLoading: isLoadingAll } = useJobs();
  const { data: companies } = useCompanies();
  const [filterCompanyId, setFilterCompanyId] = useState<string>("all");
  const { data: companyJobs, isLoading: isLoadingFiltered } = useCompanyJobs(
    filterCompanyId !== "all" ? Number(filterCompanyId) : null
  );

  const displayedJobs = filterCompanyId === "all" ? allJobs : companyJobs;
  const isLoading = filterCompanyId === "all" ? isLoadingAll : isLoadingFiltered;

  return (
    <Shell>
      <div className="space-y-8">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Job Board</h1>
            <p className="text-muted-foreground mt-2">Explore opportunities from our sponsors.</p>
          </div>
          
          <div className="w-full md:w-64">
            <Select value={filterCompanyId} onValueChange={setFilterCompanyId}>
              <SelectTrigger className="bg-card subtle-shadow h-11 rounded-xl border-none">
                <SelectValue placeholder="Filter by company" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Companies</SelectItem>
                {companies?.map((c: any) => (
                  <SelectItem key={c.companyId || c.companyid} value={String(c.companyId || c.companyid)}>
                    {c.companyName || c.companyname}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {isLoading ? (
            [1, 2, 3].map(i => <div key={i} className="h-48 bg-muted rounded-2xl animate-pulse" />)
          ) : displayedJobs?.length ? (
            displayedJobs.map((job: any, idx: number) => (
              <Card key={idx} className="subtle-shadow border-none hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                <CardContent className="p-6 space-y-4">
                  <div>
                    <h3 className="text-xl font-bold text-foreground leading-tight line-clamp-2">
                      {job.jobTitle || job.jobtitle}
                    </h3>
                    <div className="flex items-center text-primary mt-2 font-medium text-sm">
                      <Building className="w-4 h-4 mr-1.5" />
                      {job.companyName || job.companyname || "Unknown Company"}
                    </div>
                  </div>
                  
                  <div className="space-y-2 pt-4 border-t border-border/50">
                    <div className="flex items-center text-muted-foreground text-sm">
                      <MapPin className="w-4 h-4 mr-2 opacity-70" />
                      {job.location || job.city}, {job.province}
                    </div>
                    <div className="flex items-center text-muted-foreground text-sm font-medium">
                      <DollarSign className="w-4 h-4 mr-2 opacity-70" />
                      {job.payRate || job.payrate ? `$${Number(job.payRate || job.payrate).toLocaleString()}/yr` : 'Competitive'}
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))
          ) : (
            <div className="col-span-full p-12 text-center border-2 border-dashed border-border rounded-2xl text-muted-foreground flex flex-col items-center">
              <Briefcase className="w-12 h-12 mb-4 opacity-20" />
              <p>No job postings found for the selected criteria.</p>
            </div>
          )}
        </div>
      </div>
    </Shell>
  );
}
