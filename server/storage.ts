import { db } from "./db";
import {
  company, committeeMember, subCommittee, hotelRoom, session,
  attendee, student, professional, sponsor, speaker, jobAd,
  attends, speaksAt, memberOfCommittee
} from "@shared/schema";
import { eq, sql } from "drizzle-orm";

export interface IStorage {
    getCommittees(): Promise<any[]>;
    getCommitteeMembers(committeeId: number): Promise<any[]>;
    getHotelRooms(): Promise<any[]>;
    getStudentsInRoom(roomNumber: number): Promise<any[]>;
    getSessionDates(): Promise<string[]>;
    getSessionsByDate(date?: string): Promise<any[]>;
    updateSession(sessionId: number, data: any): Promise<any>;
    getSponsors(): Promise<any[]>;
    getCompanies(): Promise<any[]>;
    createCompany(data: { companyName: string }): Promise<any>;
    deleteCompany(companyId: number): Promise<void>;
    getJobsByCompany(companyId?: number): Promise<any[]>;
    getAllJobs(): Promise<any[]>;
    getAttendees(): Promise<{students: any[], professionals: any[], sponsors: any[]}>;
    createAttendee(data: any): Promise<any>;
    getStats(): Promise<any>;
}

export class DatabaseStorage implements IStorage {
    async getCommittees(): Promise<any[]> {
        return await db.select().from(subCommittee);
    }
    async getCommitteeMembers(committeeId: number): Promise<any[]> {
        return await db.select({
            memberId: committeeMember.memberId,
            firstName: committeeMember.firstName,
            lastName: committeeMember.lastName,
        }).from(memberOfCommittee)
        .innerJoin(committeeMember, eq(memberOfCommittee.memberId, committeeMember.memberId))
        .where(eq(memberOfCommittee.committeeId, committeeId));
    }
    async getHotelRooms(): Promise<any[]> {
        return await db.select().from(hotelRoom);
    }
    async getStudentsInRoom(roomNumber: number): Promise<any[]> {
        return await db.select({
            attendeeId: attendee.attendeeId,
            firstName: attendee.firstName,
            lastName: attendee.lastName,
            email: attendee.email
        }).from(student)
        .innerJoin(attendee, eq(student.attendeeId, attendee.attendeeId))
        .where(eq(student.roomNumberStaysIn, roomNumber));
    }
    async getSessionDates(): Promise<string[]> {
        const results = await db.execute(sql`SELECT DISTINCT "date" FROM session ORDER BY "date"`);
        return results.rows.map((r: any) => new Date(r.date).toISOString().split('T')[0]);
    }
    async getSessionsByDate(date?: string): Promise<any[]> {
        if (date) {
            return await db.select().from(session).where(eq(session.date, date));
        }
        return await db.select().from(session);
    }
    async updateSession(sessionId: number, data: any): Promise<any> {
        let setObj: any = {};
        if (data.date) setObj.date = data.date;
        if (data.startTime) setObj.startTime = data.startTime;
        if (data.endTime) setObj.endTime = data.endTime;
        if (data.roomLocation) setObj.roomLocation = data.roomLocation;
        
        const [updated] = await db.update(session).set(setObj).where(eq(session.sessionId, sessionId)).returning();
        return updated;
    }
    async getSponsors(): Promise<any[]> {
        return await db.select({
            attendeeId: attendee.attendeeId,
            firstName: attendee.firstName,
            lastName: attendee.lastName,
            companyName: company.companyName,
            sponsorLevel: sponsor.sponsorLevel
        }).from(sponsor)
        .innerJoin(attendee, eq(sponsor.attendeeId, attendee.attendeeId))
        .innerJoin(company, eq(sponsor.companyId, company.companyId));
    }
    async getCompanies(): Promise<any[]> {
        return await db.select().from(company);
    }
    async createCompany(data: { companyName: string }): Promise<any> {
        const [created] = await db.insert(company).values(data).returning();
        return created;
    }
    async deleteCompany(companyId: number): Promise<void> {
        await db.delete(company).where(eq(company.companyId, companyId));
    }
    async getJobsByCompany(companyId?: number): Promise<any[]> {
        if (companyId) {
            return await db.select().from(jobAd).where(eq(jobAd.postedByCompanyId, companyId));
        }
        return await db.select().from(jobAd);
    }
    async getAllJobs(): Promise<any[]> {
        return await db.select({
            jobTitle: jobAd.jobTitle,
            location: jobAd.location,
            city: jobAd.city,
            province: jobAd.province,
            payRate: jobAd.payRate,
            companyName: company.companyName
        }).from(jobAd)
        .innerJoin(company, eq(jobAd.postedByCompanyId, company.companyId));
    }
    async getAttendees(): Promise<{students: any[], professionals: any[], sponsors: any[]}> {
        const allAttendees = await db.select().from(attendee);
        const studentsList = allAttendees.filter((a) => a.attendeeType === 'Student');
        const professionalsList = allAttendees.filter((a) => a.attendeeType === 'Professional');
        const sponsorsList = allAttendees.filter((a) => a.attendeeType === 'Sponsor');
        return {
            students: studentsList,
            professionals: professionalsList,
            sponsors: sponsorsList
        };
    }
    async createAttendee(data: any): Promise<any> {
        return await db.transaction(async (tx) => {
            const [newAttendee] = await tx.insert(attendee).values({
                firstName: data.firstName,
                lastName: data.lastName,
                email: data.email,
                attendeeType: data.attendeeType,
            }).returning();
            
            if (data.attendeeType === 'Student') {
                await tx.insert(student).values({
                    attendeeId: newAttendee.attendeeId,
                    roomNumberStaysIn: data.roomNumberStaysIn || null
                });
            } else if (data.attendeeType === 'Professional') {
                await tx.insert(professional).values({
                    attendeeId: newAttendee.attendeeId
                });
            } else if (data.attendeeType === 'Sponsor') {
                await tx.insert(sponsor).values({
                    attendeeId: newAttendee.attendeeId,
                    sponsorLevel: data.sponsorLevel,
                    companyId: data.companyId
                });
            }
            return newAttendee;
        });
    }
    async getStats(): Promise<any> {
        const studentStats = await db.execute(sql`SELECT SUM(fee) as total FROM attendee WHERE attendeetype = 'Student'`);
        const profStats = await db.execute(sql`SELECT SUM(fee) as total FROM attendee WHERE attendeetype = 'Professional'`);
        
        const sponsorshipStats = await db.execute(sql`
            SELECT SUM(CASE 
                WHEN sponsorlevel = 'Platinum' THEN 10000
                WHEN sponsorlevel = 'Gold' THEN 5000
                WHEN sponsorlevel = 'Silver' THEN 2500
                WHEN sponsorlevel = 'Bronze' THEN 1000
                ELSE 0 END) as total 
            FROM sponsor
        `);
        
        return {
            registrationAmount: Number(studentStats.rows[0].total || 0) + Number(profStats.rows[0].total || 0),
            sponsorshipAmount: Number(sponsorshipStats.rows[0].total || 0)
        };
    }
}

export const storage = new DatabaseStorage();
