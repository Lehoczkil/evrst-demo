# EVRST

Website for the **Escape Velocity Rocketry Student Team** at Óbuda University.
A React single-page application that showcases the team's projects, events,
mentors, and sponsors, with a 3D rocket model on the landing page.

## Stack

- **React 19** + **TypeScript** with **Vite 7**
- **Mantine v9** for UI components and theming
- **react-router** v7 for routing
- **@tanstack/react-query** for server state
- **@react-three/fiber** + `@react-three/drei` + `three` for the 3D rocket
- **motion** for animations
- **MDX** for dynamic page content
- **bun** as the package manager / runtime

## Requirements

- [Bun](https://bun.sh/) (latest)
- Node 24+ available on the path (used by some build tooling)

## Setup

Install dependencies:

```sh
bun install
```

Create a `.env` file in the project root with the API base URL:

```env
VITE_API_URL=https://your-api-host
```

## Scripts

| Command         | Description                                |
| --------------- | ------------------------------------------ |
| `bun run dev`   | Start the Vite dev server with HMR         |
| `bun run build` | Type-check and produce a production build  |
| `bun run preview` | Preview the production build locally     |
| `bun run lint`  | Run ESLint                                 |

The production build is emitted to the `build/` directory.

## Project structure

```
src/
  api/          API client
  components/   Shared layout & UI components
  i18n/         English + Hungarian translations
  pages/        Route pages (home, dynamic, placeholder)
  styles/       Global CSS
  router.tsx    Route definitions
  main.tsx      Entry point
```

Routes are declared in `src/router.tsx`. Dynamic pages are rendered from MDX
content fetched from the backend.
