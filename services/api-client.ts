/**
 * WearDay Fashion E-Commerce - Core API Client
 * Connects Next.js Frontend with Laravel REST API (v1).
 * Features:
 * - Environment-based API URL resolution with safe fallback
 * - Full JSON request/response envelope handling
 * - Request timeout handling via AbortController
 * - Standardized error typing (Validation, Auth, Network, Timeout, Server)
 * - Automatic token management with Sanctum Bearer support
 */

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL || "http://127.0.0.1:8000/api/v1";

const DEFAULT_TIMEOUT_MS = 10000;

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number;
  to?: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  meta?: PaginationMeta;
  errors?: Record<string, string[]>;
}

export class ApiError extends Error {
  public status: number;
  public errors?: Record<string, string[]>;
  public isTimeout: boolean;
  public isNetworkError: boolean;
  public data?: any;

  constructor(
    message: string,
    status = 500,
    errors?: Record<string, string[]>,
    isTimeout = false,
    isNetworkError = false,
    data?: any
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
    this.isTimeout = isTimeout;
    this.isNetworkError = isNetworkError;
    this.data = data;
  }
}

export interface RequestOptions extends Omit<RequestInit, "body"> {
  params?: Record<string, string | number | boolean | undefined | (string | number)[]>;
  body?: any;
  timeoutMs?: number;
}

export class ApiClient {
  private static token: string | null = null;

  public static setAuthToken(token: string | null): void {
    this.token = token;
    if (typeof window !== "undefined") {
      if (token) {
        localStorage.setItem("wearday_auth_token", token);
      } else {
        localStorage.removeItem("wearday_auth_token");
      }
    }
  }

  public static getAuthToken(): string | null {
    if (this.token) return this.token;
    if (typeof window !== "undefined") {
      this.token = localStorage.getItem("wearday_auth_token");
    }
    return this.token;
  }

  public static generateSessionId(): string {
    if (typeof window !== "undefined" && window.crypto && typeof window.crypto.randomUUID === "function") {
      return "sess_" + window.crypto.randomUUID().replace(/-/g, "");
    }
    if (typeof window !== "undefined" && window.crypto && typeof window.crypto.getRandomValues === "function") {
      const bytes = new Uint8Array(16);
      window.crypto.getRandomValues(bytes);
      return "sess_" + Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
    }
    return "sess_" + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
  }

  public static getSessionId(): string {
    if (typeof window === "undefined") return "";
    let sid = localStorage.getItem("wearday_cart_session_id");
    if (!sid) {
      sid = this.generateSessionId();
      localStorage.setItem("wearday_cart_session_id", sid);
    }
    return sid;
  }

  public static rotateSessionId(): string {
    if (typeof window === "undefined") return "";
    const newSid = this.generateSessionId();
    localStorage.setItem("wearday_cart_session_id", newSid);
    return newSid;
  }

  public static getBaseUrl(): string {
    return API_BASE_URL.replace(/\/+$/, "");
  }

  public static async request<T>(
    endpoint: string,
    options: RequestOptions = {}
  ): Promise<ApiResponse<T>> {
    const { params, body, headers, timeoutMs = DEFAULT_TIMEOUT_MS, ...customConfig } = options;

    const base = this.getBaseUrl();
    const cleanEndpoint = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;
    let url = `${base}${cleanEndpoint}`;

    if (params) {
      const searchParams = new URLSearchParams();
      Object.entries(params).forEach(([key, val]) => {
        if (val === undefined || val === null || val === "") return;
        if (Array.isArray(val)) {
          val.forEach((item) => {
            if (item !== undefined && item !== null && item !== "") {
              searchParams.append(`${key}[]`, String(item));
            }
          });
        } else {
          searchParams.append(key, String(val));
        }
      });
      const queryString = searchParams.toString();
      if (queryString) {
        url += (url.includes("?") ? "&" : "?") + queryString;
      }
    }

    const token = this.getAuthToken();
    const sessionId = this.getSessionId();

    const requestHeaders: Record<string, string> = {
      Accept: "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(sessionId ? { "X-Session-Id": sessionId } : {}),
      ...(headers as Record<string, string>),
    };

    let serializedBody: BodyInit | undefined = undefined;
    if (body !== undefined && body !== null) {
      if (body instanceof FormData) {
        serializedBody = body;
        // Let fetch automatically set multipart boundary
        delete requestHeaders["Content-Type"];
      } else {
        requestHeaders["Content-Type"] = "application/json";
        serializedBody = JSON.stringify(body);
      }
    }

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    const config: RequestInit = {
      headers: requestHeaders,
      body: serializedBody,
      signal: controller.signal,
      ...customConfig,
    };

    try {
      const res = await fetch(url, config);
      clearTimeout(timeoutId);

      const contentType = res.headers.get("content-type") || "";
      const isJson = contentType.includes("application/json");
      const responseData = isJson ? await res.json() : await res.text();

      if (!res.ok) {
        // Handle 401 Unauthorized
        if (res.status === 401) {
          this.setAuthToken(null);
          if (typeof window !== "undefined") {
            window.dispatchEvent(new CustomEvent("auth:unauthorized"));
          }
        }

        const errorMessage =
          typeof responseData === "object" && responseData !== null
            ? responseData.message || `Request failed with status ${res.status}`
            : `Request failed with status ${res.status}`;

        const validationErrors =
          typeof responseData === "object" && responseData !== null
            ? responseData.errors
            : undefined;

        throw new ApiError(errorMessage, res.status, validationErrors, false, false, responseData);
      }

      // If the response is wrapped in standard Laravel JSON { success: true, data: ..., meta: ... }
      if (typeof responseData === "object" && responseData !== null && "data" in responseData) {
        return responseData as ApiResponse<T>;
      }

      // Otherwise, wrap in standard ApiResponse envelope
      return {
        success: true,
        data: responseData as T,
      };
    } catch (err: any) {
      clearTimeout(timeoutId);

      if (err instanceof ApiError) {
        throw err;
      }

      if (err.name === "AbortError") {
        throw new ApiError(
          `Request to ${endpoint} timed out after ${timeoutMs}ms.`,
          408,
          undefined,
          true,
          false
        );
      }

      throw new ApiError(
        err.message || "Network connection failed. Unable to communicate with server.",
        0,
        undefined,
        false,
        true
      );
    }
  }

  public static async get<T>(
    endpoint: string,
    params?: Record<string, string | number | boolean | undefined | (string | number)[]>,
    options?: RequestOptions
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: "GET", params, ...options });
  }

  public static async post<T>(
    endpoint: string,
    body?: any,
    options?: RequestOptions
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: "POST", body, ...options });
  }

  public static async put<T>(
    endpoint: string,
    body?: any,
    options?: RequestOptions
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: "PUT", body, ...options });
  }

  public static async patch<T>(
    endpoint: string,
    body?: any,
    options?: RequestOptions
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: "PATCH", body, ...options });
  }

  public static async delete<T>(
    endpoint: string,
    options?: RequestOptions
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: "DELETE", ...options });
  }
}

export const apiClient = ApiClient;

