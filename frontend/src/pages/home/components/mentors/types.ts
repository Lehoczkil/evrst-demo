import type { Resource } from "@/types";

export interface Mentor {
    name: string;
    email: string;
    photo?: string;
}

export type MentorResource = Resource<Mentor>