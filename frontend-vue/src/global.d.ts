type IImage = {
  aspect_ratio?: string;
  webp?: ImageSize;
  avif?: ImageSize;
};

type ImageSize = {
  desktop?: Images;
  mobile?: Images;
};

type Images = {
  [key: `x${number}`]: string | URL;
  x1: string | URL;
  x2?: string | URL;
  lqip?: string | URL;
};
