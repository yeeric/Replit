import { Shell } from "@/components/layout/Shell";
import { useStats } from "@/hooks/use-stats";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { DollarSign, Users, Building2, TrendingUp } from "lucide-react";
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from "recharts";

export default function Dashboard() {
  const { data: stats, isLoading } = useStats();

  if (isLoading) {
    return (
      <Shell>
        <div className="space-y-6">
          <div className="h-10 w-64 bg-muted rounded-lg animate-pulse" />
          <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            {[1, 2, 3].map(i => <div key={i} className="h-36 bg-muted rounded-2xl animate-pulse" />)}
          </div>
          <div className="h-96 bg-muted rounded-2xl animate-pulse" />
        </div>
      </Shell>
    );
  }

  const registrationAmount = Number(stats?.registrationAmount ?? 0);
  const sponsorshipAmount = Number(stats?.sponsorshipAmount ?? 0);
  const totalIntake = registrationAmount + sponsorshipAmount;

  const chartData = [
    { name: "Registrations", value: registrationAmount },
    { name: "Sponsorships", value: sponsorshipAmount },
  ];

  const COLORS = ['hsl(221, 83%, 53%)', 'hsl(142, 71%, 45%)'];

  const statCards = [
    {
      label: "Total Conference Revenue",
      value: `$${totalIntake.toLocaleString()}`,
      icon: TrendingUp,
      desc: "Combined registration + sponsorship",
      color: "text-blue-600",
      bg: "bg-blue-50",
    },
    {
      label: "Registration Fees",
      value: `$${registrationAmount.toLocaleString()}`,
      icon: Users,
      desc: "From students & professionals",
      color: "text-emerald-600",
      bg: "bg-emerald-50",
    },
    {
      label: "Sponsorship Amounts",
      value: `$${sponsorshipAmount.toLocaleString()}`,
      icon: Building2,
      desc: "From corporate partners",
      color: "text-violet-600",
      bg: "bg-violet-50",
    },
  ];

  return (
    <Shell>
      <div className="space-y-8">
        <div>
          <p className="text-sm font-semibold text-primary uppercase tracking-widest mb-1">Finance</p>
          <h1 className="text-4xl font-bold tracking-tight">Conference Overview</h1>
          <p className="text-muted-foreground mt-2 text-base">Real-time financial summary of the CISC 332 conference.</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
          {statCards.map((card) => (
            <Card key={card.label} className="subtle-shadow border border-border/60 rounded-2xl overflow-hidden">
              <CardContent className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className={`${card.bg} ${card.color} w-11 h-11 rounded-xl flex items-center justify-center`}>
                    <card.icon className="w-5 h-5" />
                  </div>
                </div>
                <div className="text-3xl font-bold mb-1">{card.value}</div>
                <div className="text-sm font-medium text-foreground/70">{card.label}</div>
                <div className="text-xs text-muted-foreground mt-1">{card.desc}</div>
              </CardContent>
            </Card>
          ))}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
          <Card className="subtle-shadow border border-border/60 rounded-2xl">
            <CardHeader className="pb-2">
              <CardTitle className="text-lg">Revenue Breakdown</CardTitle>
              <CardDescription>Proportion of income streams</CardDescription>
            </CardHeader>
            <CardContent className="h-[300px] flex justify-center items-center">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={chartData}
                    cx="50%"
                    cy="50%"
                    innerRadius={75}
                    outerRadius={115}
                    paddingAngle={4}
                    dataKey="value"
                  >
                    {chartData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} strokeWidth={0} />
                    ))}
                  </Pie>
                  <Tooltip
                    formatter={(value: number) => [`$${value.toLocaleString()}`, '']}
                    contentStyle={{ borderRadius: '12px', border: '1px solid #e5e7eb', boxShadow: '0 4px 20px rgba(0,0,0,0.08)', fontFamily: 'Inter' }}
                  />
                  <Legend
                    verticalAlign="bottom"
                    height={36}
                    iconType="circle"
                    formatter={(value) => <span style={{ color: '#374151', fontSize: '13px', fontWeight: 500 }}>{value}</span>}
                  />
                </PieChart>
              </ResponsiveContainer>
            </CardContent>
          </Card>

          <Card className="subtle-shadow border border-border/60 rounded-2xl">
            <CardHeader className="pb-2">
              <CardTitle className="text-lg">Fee Structure</CardTitle>
              <CardDescription>Sponsorship tier values & registration fees</CardDescription>
            </CardHeader>
            <CardContent className="pt-2">
              <div className="space-y-3">
                {[
                  { tier: 'Platinum Sponsor', amount: '$10,000', color: 'bg-slate-700 text-white' },
                  { tier: 'Gold Sponsor', amount: '$5,000', color: 'bg-yellow-400 text-yellow-900' },
                  { tier: 'Silver Sponsor', amount: '$2,500', color: 'bg-slate-300 text-slate-700' },
                  { tier: 'Bronze Sponsor', amount: '$1,000', color: 'bg-amber-700 text-amber-50' },
                  { tier: 'Professional Fee', amount: '$100', color: 'bg-blue-100 text-blue-800' },
                  { tier: 'Student Fee', amount: '$50', color: 'bg-emerald-100 text-emerald-800' },
                ].map((item) => (
                  <div key={item.tier} className="flex items-center justify-between px-4 py-2.5 rounded-xl bg-muted/40">
                    <div className="flex items-center gap-3">
                      <span className={`inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold ${item.color}`}>
                        {item.tier.replace(' Sponsor', '').replace(' Fee', '')}
                      </span>
                      <span className="text-sm text-muted-foreground">{item.tier}</span>
                    </div>
                    <span className="font-semibold text-sm">{item.amount}</span>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </Shell>
  );
}
