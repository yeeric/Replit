import { Switch, Route } from "wouter";
import { queryClient } from "./lib/queryClient";
import { QueryClientProvider } from "@tanstack/react-query";
import { Toaster } from "@/components/ui/toaster";
import { TooltipProvider } from "@/components/ui/tooltip";

import Dashboard from "@/pages/dashboard";
import Committees from "@/pages/committees";
import Hotels from "@/pages/hotels";
import Schedule from "@/pages/schedule";
import Sponsors from "@/pages/sponsors";
import Jobs from "@/pages/jobs";
import Attendees from "@/pages/attendees";
import NotFound from "@/pages/not-found";

function Router() {
  return (
    <Switch>
      <Route path="/" component={Dashboard}/>
      <Route path="/committees" component={Committees}/>
      <Route path="/hotels" component={Hotels}/>
      <Route path="/schedule" component={Schedule}/>
      <Route path="/sponsors" component={Sponsors}/>
      <Route path="/jobs" component={Jobs}/>
      <Route path="/attendees" component={Attendees}/>
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <Toaster />
        <Router />
      </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
