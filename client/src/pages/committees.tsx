import { useState } from "react";
import { Shell } from "@/components/layout/Shell";
import { useCommittees, useCommitteeMembers } from "@/hooks/use-committees";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card } from "@/components/ui/card";
import { UsersRound } from "lucide-react";

export default function Committees() {
  const { data: committees, isLoading: isLoadingCommittees } = useCommittees();
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const { data: members, isLoading: isLoadingMembers } = useCommitteeMembers(selectedId);

  return (
    <Shell>
      <div className="space-y-8">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Sub-Committees</h1>
          <p className="text-muted-foreground mt-2">Manage and view committee members.</p>
        </div>

        <div className="max-w-md">
          <Select 
            onValueChange={(v) => setSelectedId(Number(v))} 
            disabled={isLoadingCommittees}
          >
            <SelectTrigger className="bg-card subtle-shadow h-12 rounded-xl border-none">
              <SelectValue placeholder="Select a committee..." />
            </SelectTrigger>
            <SelectContent>
              {committees?.map((c: any) => (
                <SelectItem key={c.committeeId || c.committeeid} value={String(c.committeeId || c.committeeid)}>
                  {c.committeeName || c.committeename}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {selectedId ? (
          <Card className="subtle-shadow border-none overflow-hidden rounded-2xl">
            <div className="p-6 border-b border-border/50 bg-card/50">
              <h3 className="font-semibold text-lg flex items-center gap-2">
                <UsersRound className="w-5 h-5 text-muted-foreground" />
                Committee Members
              </h3>
            </div>
            {isLoadingMembers ? (
              <div className="p-8 text-center text-muted-foreground">Loading members...</div>
            ) : members?.length ? (
              <Table>
                <TableHeader className="bg-muted/50">
                  <TableRow>
                    <TableHead className="w-[100px]">ID</TableHead>
                    <TableHead>First Name</TableHead>
                    <TableHead>Last Name</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {members.map((m: any) => (
                    <TableRow key={m.memberId || m.memberid} className="hover:bg-muted/30">
                      <TableCell className="font-mono text-muted-foreground">{m.memberId || m.memberid}</TableCell>
                      <TableCell className="font-medium">{m.firstName || m.firstname}</TableCell>
                      <TableCell className="font-medium">{m.lastName || m.lastname}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            ) : (
              <div className="p-12 text-center text-muted-foreground flex flex-col items-center">
                <UsersRound className="w-12 h-12 mb-4 opacity-20" />
                <p>No members found for this committee.</p>
              </div>
            )}
          </Card>
        ) : (
          <div className="h-64 border-2 border-dashed border-border rounded-2xl flex items-center justify-center text-muted-foreground">
            Please select a committee from the dropdown above.
          </div>
        )}
      </div>
    </Shell>
  );
}
