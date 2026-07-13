import { createAuth } from '@pinooxhq/auth';

/** Zero-config from __PINOOX__.auth (manager app.php auth). */
export const auth = createAuth({
  key: 'manager_pinoox',
  mode: 'jwt',
  debug: import.meta.env.DEV,
});

export default auth;
