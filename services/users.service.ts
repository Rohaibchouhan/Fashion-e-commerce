import { User, Address, UserRoleInfo } from "@/types/auth";
import { ApiClient, ApiResponse, PaginationMeta } from "@/services/api-client";

export interface GetUsersParams {
  search?: string;
  role?: string;
  status?: string;
  sort_by?: string;
  sort_dir?: "asc" | "desc";
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
}

export interface CreateUserData {
  name?: string;
  first_name?: string;
  last_name?: string;
  email: string;
  password: string;
  phone?: string;
  role_id?: number;
  role?: string;
  status?: "active" | "inactive" | "pending_activation" | "suspended";
}

export interface UpdateUserData {
  name?: string;
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
  password?: string;
  role_id?: number;
  role?: string;
  status?: "active" | "inactive" | "pending_activation" | "suspended";
}

export class UsersService {
  /**
   * Fetch paginated users with filters and search.
   */
  static async getUsers(params: GetUsersParams = {}): Promise<{
    users: User[];
    meta?: PaginationMeta;
  }> {
    const res = await ApiClient.get<User[]>("/admin/users", params as any);
    return {
      users: res.data || [],
      meta: res.meta,
    };
  }

  /**
   * Fetch all roles defined in the system.
   */
  static async getRoles(): Promise<UserRoleInfo[]> {
    const res = await ApiClient.get<UserRoleInfo[]>("/admin/users/roles");
    return res.data || [];
  }

  /**
   * Get individual user details.
   */
  static async getUser(id: string | number): Promise<User> {
    const res = await ApiClient.get<User>(`/admin/users/${id}`);
    return res.data;
  }

  /**
   * Create a new user.
   */
  static async createUser(data: CreateUserData): Promise<User> {
    const res = await ApiClient.post<User>("/admin/users", data);
    return res.data;
  }

  /**
   * Update an existing user.
   */
  static async updateUser(id: string | number, data: UpdateUserData): Promise<User> {
    const res = await ApiClient.put<User>(`/admin/users/${id}`, data);
    return res.data;
  }

  /**
   * Update user account status (active, inactive, suspended, pending_activation).
   */
  static async updateStatus(
    id: string | number,
    status: "active" | "inactive" | "pending_activation" | "suspended"
  ): Promise<User> {
    const res = await ApiClient.patch<User>(`/admin/users/${id}/status`, { status });
    return res.data;
  }

  /**
   * Delete a user safely (checks orders & super admin protection).
   */
  static async deleteUser(id: string | number): Promise<{ status: string; message: string }> {
    const res = await ApiClient.delete<{ status: string; message: string }>(`/admin/users/${id}`);
    return (res.data || res) as any;
  }

  /**
   * Get addresses for a specific user.
   */
  static async getUserAddresses(userId: string | number): Promise<Address[]> {
    const res = await ApiClient.get<Address[]>(`/admin/users/${userId}/addresses`);
    return res.data || [];
  }

  /**
   * Add address to a user.
   */
  static async addUserAddress(userId: string | number, address: Omit<Address, "id">): Promise<Address> {
    const res = await ApiClient.post<Address>(`/admin/users/${userId}/addresses`, {
      first_name: address.firstName,
      last_name: address.lastName,
      street: address.street,
      apartment: address.apartment,
      city: address.city,
      state: address.state,
      zip_code: address.zipCode,
      country: address.country,
      phone: address.phone,
      is_default_shipping: address.isDefaultShipping,
      is_default_billing: address.isDefaultBilling,
    });
    return res.data;
  }

  /**
   * Delete address from a user.
   */
  static async deleteUserAddress(userId: string | number, addressId: string | number): Promise<void> {
    await ApiClient.delete(`/admin/users/${userId}/addresses/${addressId}`);
  }
}
