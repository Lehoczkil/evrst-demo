import type {
  InterceptorErrorFunction,
  InterceptorFunction,
  InterceptorHandler,
  InterceptorManagerType,
} from './FetchWrapper.interface';

export class InterceptorManager<T> implements InterceptorManagerType<T> {
  private handler: InterceptorHandler<T>[];

  constructor() {
    this.handler = [];
  }

  use(fullfield?: InterceptorFunction<T>, reject?: InterceptorErrorFunction<T>) {
    this.handler.push({ fullfield, reject });

    return this.handler[this.handler.length - 1];
  }

  async forEach(data: T, failed: boolean = false): Promise<void> {
    for (const fn of this.handler) {
      if (fn.reject && failed) {
        fn.reject(data);
      }

      if (fn.fullfield && !failed) {
        data = await fn.fullfield(data);
      }
    }
  }
}
