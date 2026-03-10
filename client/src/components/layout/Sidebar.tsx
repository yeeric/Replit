import { Link, useLocation } from "wouter";
import {
  LayoutDashboard,
  Users,
  BedDouble,
  CalendarDays,
  Building2,
  Briefcase,
  UserPlus,
} from "lucide-react";
import { cn } from "@/lib/utils";

const navItems = [
  { name: "Finance Overview", href: "/", icon: LayoutDashboard },
  { name: "Committees", href: "/committees", icon: Users },
  { name: "Hotel Rooms", href: "/hotels", icon: BedDouble },
  { name: "Schedule", href: "/schedule", icon: CalendarDays },
  { name: "Sponsors & Companies", href: "/sponsors", icon: Building2 },
  { name: "Job Board", href: "/jobs", icon: Briefcase },
  { name: "Attendees", href: "/attendees", icon: UserPlus },
];

export function Sidebar() {
  const [location] = useLocation();

  return (
    <aside className="w-64 h-screen border-r border-border bg-white flex flex-col fixed left-0 top-0 z-40 shadow-sm">
      <div className="px-5 py-6 border-b border-border/60">
        <div className="flex items-center gap-3">
          <div className="w-9 h-9 rounded-xl bg-primary flex items-center justify-center shadow-md shadow-primary/30">
            <span className="text-primary-foreground text-base font-black">C</span>
          </div>
          <div>
            <h1 className="text-base font-bold text-foreground leading-tight" data-testid="text-logo">ConfManager</h1>
            <p className="text-xs text-muted-foreground">CISC 332 • Admin Panel</p>
          </div>
        </div>
      </div>

      <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        {navItems.map((item) => {
          const isActive = location === item.href;
          return (
            <Link key={item.href} href={item.href}>
              <div
                data-testid={`link-nav-${item.name.toLowerCase().replace(/\s+/g, '-')}`}
                className={cn(
                  "flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-150 cursor-pointer group",
                  isActive
                    ? "bg-primary text-primary-foreground font-semibold shadow-sm shadow-primary/20"
                    : "text-muted-foreground hover:bg-muted hover:text-foreground"
                )}
              >
                <item.icon
                  className={cn(
                    "w-4.5 h-4.5 shrink-0",
                    isActive ? "opacity-100" : "opacity-60 group-hover:opacity-90"
                  )}
                  size={18}
                />
                <span className="text-sm">{item.name}</span>
              </div>
            </Link>
          );
        })}
      </nav>

      <div className="px-5 py-4 border-t border-border/60">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
            <span className="text-primary text-sm font-bold">A</span>
          </div>
          <div>
            <p className="text-xs font-semibold text-foreground">Conference Admin</p>
            <p className="text-[11px] text-muted-foreground">Organizer</p>
          </div>
        </div>
      </div>
    </aside>
  );
}
