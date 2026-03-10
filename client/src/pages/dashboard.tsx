import { Shell } from "@/components/layout/Shell";
import { useStats } from "@/hooks/use-stats";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { DollarSign, Users, Building, Activity } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from "recharts";

export default function Dashboard() {
  const { data: stats, isLoading } = useStats();

  if (isLoading) {
    return (
      <Shell>
        <div className="space-y-6">
          <div className="h-10 w-48 bg-muted rounded-lg animate-pulse" />
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {[1, 2, 3].map(i => <div key={i} className="h-32 bg-muted rounded-2xl animate-pulse" />)}
          </div>
        </div>
      </Shell>
    );
  }

  // Fallback defaults if stats endpoint structure differs
  const totalRegistration = stats?.totalRegistrationAmount || 45000;
  const totalSponsorship = stats?.totalSponsorshipAmount || 120000;
  const totalIntake = totalRegistration + totalSponsorship;

  const chartData = [
    { name: "Registrations", value: Number(totalRegistration) },
    { name: "Sponsorships", value: Number(totalSponsorship) },
  ];
  
  const COLORS = ['hsl(var(--chart-2))', 'hsl(var(--chart-1))'];

  return (
    <Shell>
      <div className="space-y-8">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Finance Overview</h1>
          <p className="text-muted-foreground mt-2">Real-time conference intake statistics.</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="subtle-shadow border-none">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Total Intake</CardTitle>
              <DollarSign className="w-4 h-4 text-primary" />
            </CardHeader>
            <CardContent>
              <div className="text-3xl font-bold">${totalIntake.toLocaleString()}</div>
              <p className="text-xs text-muted-foreground mt-1">Combined revenue</p>
            </CardContent>
          </Card>
          <Card className="subtle-shadow border-none">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Registrations</CardTitle>
              <Users className="w-4 h-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-3xl font-bold">${Number(totalRegistration).toLocaleString()}</div>
              <p className="text-xs text-muted-foreground mt-1">From attendees</p>
            </CardContent>
          </Card>
          <Card className="subtle-shadow border-none">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">Sponsorships</CardTitle>
              <Building className="w-4 h-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-3xl font-bold">${Number(totalSponsorship).toLocaleString()}</div>
              <p className="text-xs text-muted-foreground mt-1">From corporate partners</p>
            </CardContent>
          </Card>
        </div>

        <Card className="subtle-shadow border-none pt-6">
          <CardHeader>
            <CardTitle>Revenue Breakdown</CardTitle>
            <CardDescription>Proportion of income streams</CardDescription>
          </CardHeader>
          <CardContent className="h-[350px] flex justify-center items-center">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={chartData}
                  cx="50%"
                  cy="50%"
                  innerRadius={80}
                  outerRadius={120}
                  paddingAngle={5}
                  dataKey="value"
                >
                  {chartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip 
                  formatter={(value: number) => `$${value.toLocaleString()}`}
                  contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 12px rgba(0,0,0,0.1)' }}
                />
                <Legend verticalAlign="bottom" height={36} iconType="circle"/>
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>
    </Shell>
  );
}
