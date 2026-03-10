import { ReactNode } from "react";
import { Sidebar } from "./Sidebar";

export function Shell({ children }: { children: ReactNode }) {
  return (
    <div className="min-h-screen bg-background flex">
      <Sidebar />
      <main className="flex-1 ml-64 p-8 md:p-12 lg:p-16 max-w-7xl">
        <div className="animate-in fade-in slide-in-from-bottom-4 duration-500 ease-out fill-mode-forwards">
          {children}
        </div>
      </main>
    </div>
  );
}
