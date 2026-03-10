import type { Express } from "express";
import type { Server } from "http";
import { storage } from "./storage";
import { api } from "@shared/routes";
import { z } from "zod";

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {
  
  app.get(api.committees.list.path, async (req, res) => {
    const committees = await storage.getCommittees();
    res.json(committees);
  });

  app.get(api.committees.members.path, async (req, res) => {
    const members = await storage.getCommitteeMembers(Number(req.params.id));
    res.json(members);
  });

  app.get(api.hotelRooms.list.path, async (req, res) => {
    const rooms = await storage.getHotelRooms();
    res.json(rooms);
  });

  app.get(api.hotelRooms.students.path, async (req, res) => {
    const students = await storage.getStudentsInRoom(Number(req.params.id));
    res.json(students);
  });

  app.get(api.sessions.dates.path, async (req, res) => {
    const dates = await storage.getSessionDates();
    res.json(dates);
  });

  app.get(api.sessions.list.path, async (req, res) => {
    const sessions = await storage.getSessionsByDate(req.query.date as string);
    res.json(sessions);
  });

  app.put(api.sessions.update.path, async (req, res) => {
    try {
      const input = api.sessions.update.input.parse(req.body);
      const session = await storage.updateSession(Number(req.params.id), input);
      if (!session) {
        return res.status(404).json({ message: "Session not found" });
      }
      res.json(session);
    } catch (err) {
      res.status(400).json({ message: "Invalid input" });
    }
  });

  app.get(api.sponsors.list.path, async (req, res) => {
    const sponsors = await storage.getSponsors();
    res.json(sponsors);
  });

  app.get(api.companies.list.path, async (req, res) => {
    const companies = await storage.getCompanies();
    res.json(companies);
  });

  app.post(api.companies.create.path, async (req, res) => {
    try {
      const input = api.companies.create.input.parse(req.body);
      const company = await storage.createCompany(input);
      res.status(201).json(company);
    } catch (err) {
      res.status(400).json({ message: "Invalid input" });
    }
  });

  app.delete(api.companies.delete.path, async (req, res) => {
    await storage.deleteCompany(Number(req.params.id));
    res.status(204).send();
  });

  app.get(api.companies.jobs.path, async (req, res) => {
    const jobs = await storage.getJobsByCompany(Number(req.params.id));
    res.json(jobs);
  });

  app.get(api.jobs.list.path, async (req, res) => {
    const jobs = await storage.getAllJobs();
    res.json(jobs);
  });

  app.get(api.attendees.list.path, async (req, res) => {
    const attendees = await storage.getAttendees();
    res.json(attendees);
  });

  app.post(api.attendees.create.path, async (req, res) => {
    try {
      const input = api.attendees.create.input.parse(req.body);
      const attendee = await storage.createAttendee(input);
      res.status(201).json(attendee);
    } catch (err) {
        if (err instanceof z.ZodError) {
          return res.status(400).json({
            message: err.errors[0].message,
            field: err.errors[0].path.join('.'),
          });
        }
        res.status(400).json({ message: "Invalid input" });
    }
  });

  app.get(api.stats.intake.path, async (req, res) => {
    const stats = await storage.getStats();
    res.json(stats);
  });

  return httpServer;
}
