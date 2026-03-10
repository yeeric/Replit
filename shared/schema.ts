import { pgTable, integer, varchar, date, time, numeric, text, primaryKey } from "drizzle-orm/pg-core";
import { z } from "zod";

export const company = pgTable("company", {
    companyId: integer("companyid").primaryKey().generatedByDefaultAsIdentity(),
    companyName: varchar("companyname", { length: 100 }).notNull(),
});

export const committeeMember = pgTable("committeemember", {
    memberId: integer("memberid").primaryKey().generatedByDefaultAsIdentity(),
    firstName: varchar("firstname", { length: 100 }).notNull(),
    lastName: varchar("lastname", { length: 100 }).notNull(),
});

export const subCommittee = pgTable("subcommittee", {
    committeeId: integer("committeeid").primaryKey().generatedByDefaultAsIdentity(),
    committeeName: varchar("committeename", { length: 100 }).notNull(),
    chairMemberId: integer("chairmemberid").notNull(),
});

export const hotelRoom = pgTable("hotelroom", {
    roomNumber: integer("roomnumber").primaryKey(),
    numberOfBeds: integer("numberofbeds").notNull(),
});

export const session = pgTable("session", {
    sessionId: integer("sessionid").primaryKey().generatedByDefaultAsIdentity(),
    sessionName: varchar("sessionname", { length: 150 }).notNull(),
    date: date("date").notNull(),
    startTime: time("starttime").notNull(),
    endTime: time("endtime").notNull(),
    roomLocation: varchar("roomlocation", { length: 100 }).notNull(),
});

export const attendee = pgTable("attendee", {
    attendeeId: integer("attendeeid").primaryKey().generatedByDefaultAsIdentity(),
    firstName: varchar("firstname", { length: 50 }).notNull(),
    lastName: varchar("lastname", { length: 50 }).notNull(),
    email: varchar("email", { length: 100 }).notNull().unique(),
    attendeeType: text("attendeetype").notNull(),
    fee: numeric("fee", { precision: 10, scale: 2 }),
});

export const student = pgTable("student", {
    attendeeId: integer("attendeeid").primaryKey(),
    roomNumberStaysIn: integer("roomnumberstaysin"),
});

export const professional = pgTable("professional", {
    attendeeId: integer("attendeeid").primaryKey(),
});

export const sponsor = pgTable("sponsor", {
    attendeeId: integer("attendeeid").primaryKey(),
    sponsorLevel: text("sponsorlevel").notNull(),
    emailsSent: integer("emailssent").notNull().default(0),
    maxEmailsAllowed: integer("maxemailsallowed"),
    companyId: integer("companyid").notNull(),
});

export const speaker = pgTable("speaker", {
    speakerId: integer("speakerid").primaryKey().generatedByDefaultAsIdentity(),
    firstName: varchar("firstname", { length: 50 }).notNull(),
    lastName: varchar("lastname", { length: 50 }).notNull(),
    attendeeId: integer("attendeeid"),
});

export const jobAd = pgTable("jobad", {
    jobTitle: varchar("jobtitle", { length: 150 }).notNull(),
    location: varchar("location", { length: 150 }).notNull(),
    city: varchar("city", { length: 100 }).notNull(),
    province: varchar("province", { length: 100 }).notNull(),
    payRate: numeric("payrate", { precision: 10, scale: 2 }).notNull(),
    postedByCompanyId: integer("postedbycompanyid").notNull(),
}, (table) => {
    return {
        pk: primaryKey({ columns: [table.postedByCompanyId, table.jobTitle] })
    };
});

export const attends = pgTable("attends", {
    attendeeId: integer("attendeeid").notNull(),
    sessionId: integer("sessionid").notNull(),
}, (table) => {
    return {
        pk: primaryKey({ columns: [table.attendeeId, table.sessionId] })
    };
});

export const speaksAt = pgTable("speaksat", {
    speakerId: integer("speakerid").notNull(),
    sessionId: integer("sessionid").notNull(),
}, (table) => {
    return {
        pk: primaryKey({ columns: [table.speakerId, table.sessionId] })
    };
});

export const memberOfCommittee = pgTable("memberofcommittee", {
    memberId: integer("memberid").notNull(),
    committeeId: integer("committeeid").notNull(),
}, (table) => {
    return {
        pk: primaryKey({ columns: [table.memberId, table.committeeId] })
    };
});
