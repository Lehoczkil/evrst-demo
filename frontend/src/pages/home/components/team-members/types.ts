import type { Resource } from "@/types";

export interface TeamMember {
    name: string;
    degree?: string;
    photo?: string;
    positions?: Resource<TeamMemberGroup>[];
    main_position?: Resource<TeamMemberGroup>;
}

export interface TeamMemberGroup {
    name: string;
}

export type TeamMemberResource = Resource<TeamMember>
