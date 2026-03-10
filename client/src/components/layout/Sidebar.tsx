import { Link, useLocation } from "wouter";
import { 
  LayoutDashboard, 
  Users, 
  Hotel, 
  CalendarDays, 
  Building2, 
  Briefcase,
  UserPlus
} from "lucide-react";
import { cn } from "@/lib/utils";

const navItems = [
  { name: "Dashboard", href: "/", icon: LayoutDashboard },
  { name: "Committees", href: "/committees", icon: Users },
  { name: "Hotel Rooms", href: "/hotels", icon: Hotel },
  { name: "Schedule", href: "/schedule", icon: CalendarDays },
  { name: "Sponsors & Cos", href: "/sponsors", icon: Building2 },
  { name: "Job Board", href: "/jobs", icon: Briefcase },
  { name: "Attendees", href: "/attendees", icon: UserPlus },
];

export function Sidebar() {
  const [location] = useLocation();

  return (
    <aside className="w-64 h-screen border-r border-border bg-sidebar-background flex flex-col fixed left-0 top-0 z-40">
      <div className="p-6">
        <h1 className="text-xl font-bold tracking-tight text-sidebar-primary flex items-center gap-2">
          <div className="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
            <span className="text-primary-foreground text-sm font-black">C</span>
          </div>
          ConfMaster
        </h1>
      </div>
      
      <nav className="flex-1 px-4 space-y-1 overflow-y-auto">
        {navItems.map((item) => {
          const isActive = location === item.href;
          return (
            <Link key={item.href} href={item.href}>
              <div
                className={cn(
                  "flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 cursor-pointer group",
                  isActive 
                    ? "bg-primary text-primary-foreground font-medium shadow-md shadow-primary/10" 
                    : "text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                )}
              >
                <item.icon className={cn("w-5 h-5", isActive ? "opacity-100" : "opacity-70 group-hover:opacity-100")} />
                {item.name}
              </div>
            </Link>
          );
        })}
      </nav>
      
      <div className="p-6 border-t border-border mt-auto">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground font-semibold">
            A
          </div>
          <div>
            <p className="text-sm font-semibold">Admin User</p>
            <p className="text-xs text-muted-foreground">Organizer</p>
          </div>
        </div>
      </div>
    </aside>
  );
}
