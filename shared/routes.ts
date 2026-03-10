import { z } from 'zod';

export const errorSchemas = {
  validation: z.object({ message: z.string(), field: z.string().optional() }),
  notFound: z.object({ message: z.string() }),
  internal: z.object({ message: z.string() }),
};

export const api = {
  committees: {
    list: {
      method: 'GET' as const,
      path: '/api/committees' as const,
      responses: { 200: z.array(z.any()) },
    },
    members: {
      method: 'GET' as const,
      path: '/api/committees/:id/members' as const,
      responses: { 200: z.array(z.any()) },
    }
  },
  hotelRooms: {
    list: {
      method: 'GET' as const,
      path: '/api/hotel-rooms' as const,
      responses: { 200: z.array(z.any()) },
    },
    students: {
      method: 'GET' as const,
      path: '/api/hotel-rooms/:id/students' as const,
      responses: { 200: z.array(z.any()) },
    }
  },
  sessions: {
    dates: {
      method: 'GET' as const,
      path: '/api/sessions/dates' as const,
      responses: { 200: z.array(z.string()) },
    },
    list: {
      method: 'GET' as const,
      path: '/api/sessions' as const,
      input: z.object({ date: z.string().optional() }).optional(),
      responses: { 200: z.array(z.any()) },
    },
    update: {
      method: 'PUT' as const,
      path: '/api/sessions/:id' as const,
      input: z.object({
        date: z.string().optional(),
        startTime: z.string().optional(),
        endTime: z.string().optional(),
        roomLocation: z.string().optional()
      }),
      responses: { 200: z.any(), 404: errorSchemas.notFound }
    }
  },
  sponsors: {
    list: {
      method: 'GET' as const,
      path: '/api/sponsors' as const,
      responses: { 200: z.array(z.any()) },
    }
  },
  companies: {
    list: {
      method: 'GET' as const,
      path: '/api/companies' as const,
      responses: { 200: z.array(z.any()) },
    },
    create: {
      method: 'POST' as const,
      path: '/api/companies' as const,
      input: z.object({ companyName: z.string() }),
      responses: { 201: z.any() },
    },
    delete: {
      method: 'DELETE' as const,
      path: '/api/companies/:id' as const,
      responses: { 204: z.void(), 404: errorSchemas.notFound },
    },
    jobs: {
      method: 'GET' as const,
      path: '/api/companies/:id/jobs' as const,
      responses: { 200: z.array(z.any()) },
    }
  },
  jobs: {
    list: {
      method: 'GET' as const,
      path: '/api/jobs' as const,
      responses: { 200: z.array(z.any()) },
    }
  },
  attendees: {
    list: {
      method: 'GET' as const,
      path: '/api/attendees' as const,
      responses: { 200: z.any() }, // Using any to pass combined tabs object
    },
    create: {
      method: 'POST' as const,
      path: '/api/attendees' as const,
      input: z.object({
        firstName: z.string().min(1, "First name is required"),
        lastName: z.string().min(1, "Last name is required"),
        email: z.string().email(),
        attendeeType: z.enum(['Student', 'Professional', 'Sponsor']),
        roomNumberStaysIn: z.coerce.number().optional(),
        sponsorLevel: z.enum(['Platinum', 'Gold', 'Silver', 'Bronze']).optional(),
        companyId: z.coerce.number().optional(),
      }),
      responses: { 201: z.any(), 400: errorSchemas.validation },
    }
  },
  stats: {
    intake: {
      method: 'GET' as const,
      path: '/api/stats/intake' as const,
      responses: { 200: z.any() },
    }
  }
};

export function buildUrl(path: string, params?: Record<string, string | number>): string {
  let url = path;
  if (params) {
    Object.entries(params).forEach(([key, value]) => {
      if (url.includes(`:${key}`)) {
        url = url.replace(`:${key}`, String(value));
      }
    });
  }
  return url;
}
