declare class RatingButton {
    protected el: HTMLElement;
    icon: HTMLElement | null;
    rated: boolean;
    type: string;
    id: string;
    constructor(el: HTMLElement);
    toggle(): Promise<void>;
    refreshStyle(): void;
    classToList(className: string): string[];
}

declare interface RatingButtonModule {
    RatingButton: typeof RatingButton;
    ready: typeof ready;
}

declare const ready: Promise<void>;

export declare function useRatingButtons(): Promise<RatingButtonModule>;

export { }
