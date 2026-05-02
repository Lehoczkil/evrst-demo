export const fetcher = async <T>(promise?: Promise<T>) => {
  try {
    const data = await promise;
    return { data };
  } catch (error: any) {
    return { error };
  }
};
