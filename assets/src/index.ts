import { RatingButtonModule } from '~feedback/rating-button';

export async function useRatingButtons(): Promise<RatingButtonModule> {
  const module = await import('./rating-button');

  await module.ready;

  return module;
}
