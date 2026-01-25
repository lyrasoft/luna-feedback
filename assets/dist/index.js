async function useRatingButtons() {
  const module = await import("./chunks/rating-button.js");
  await module.ready;
  return module;
}
export {
  useRatingButtons
};
//# sourceMappingURL=index.js.map
