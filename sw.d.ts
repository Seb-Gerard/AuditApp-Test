/// <reference lib="webworker" />

declare const self: ServiceWorkerGlobalScope;
declare const clients: Clients;
declare const skipWaiting: () => Promise<void>; 