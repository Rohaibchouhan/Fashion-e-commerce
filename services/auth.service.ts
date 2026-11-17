import { User, Address } from "@/types/auth";
import { ApiClient } from "@/services/api-client";

export class AuthService {
  /**
   * Log into customer or admin account.
   */
  static async login(email: string, password: string): Promise<{ user: User; token: string }> {
    const res = await ApiClient.post<{ user: User; token: string }>("/auth/login", {
      email,
      password,
    });

    const data = res.data;
    if (data?.token) {
      ApiClient.setAuthToken(data.token);
    }
    return data;
  }

  /**
   * Register a new customer account.
   */
  static async register(
    name: string,
    email: string,
    password: string,
    phone?: string
  ): Promise<{ user: User; token: string }> {
    const res = await ApiClient.post<{ user: User; token: string }>("/auth/register", {
      name,
      email,
      password,
      phone,
    });

    const data = res.data;
    if (data?.token) {
      ApiClient.setAuthToken(data.token);
    }
    return data;
  }

  /**
   * Fetch authenticated user details.
   */
  static async getCurrentUser(): Promise<User> {
    const res = await ApiClient.get<User>("/auth/me");
    return res.data;
  }

  /**
   * Log out of current session.
   */
  static async logout(): Promise<void> {
    try {
      await ApiClient.post("/auth/logout");
    } finally {
      ApiClient.setAuthToken(null);
      ApiClient.rotateSessionId();
    }
  }

  /**
   * Send password reset email instructions.
   */
  static async forgotPassword(email: string): Promise<{ success: boolean; message: string }> {
    const res = await ApiClient.post<{ success: boolean; message: string }>("/auth/forgot-password", {
      email,
    });
    return {
      success: res.success,
      message: res.message || `Password reset instructions sent to ${email}.`,
    };
  }

  /**
   * Reset password with reset token.
   */
  static async resetPassword(
    token: string,
    newPassword: string,
    email?: string
  ): Promise<{ success: boolean }> {
    const res = await ApiClient.post<{ success: boolean }>("/auth/reset-password", {
      token,
      email: email || "alex.morgan@example.com",
      password: newPassword,
    });
    return { success: res.success };
  }

  /**
   * Update profile information.
   */
  static async updateProfile(data: Partial<User>): Promise<User> {
    const res = await ApiClient.put<User>("/auth/profile", data);
    return res.data;
  }

  /**
   * Change user password.
   */
  static async changePassword(currentPassword: string, newPassword: string, newPasswordConfirmation?: string): Promise<void> {
    await ApiClient.post("/auth/change-password", {
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirmation: newPasswordConfirmation || newPassword,
    });
  }

  /**
   * Add a new saved address.
   */
  static async addAddress(address: Omit<Address, "id">): Promise<Address> {
    const res = await ApiClient.post<Address>("/auth/addresses", {
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
   * Update an existing address.
   */
  static async updateAddress(id: string, address: Partial<Address>): Promise<Address> {
    const payload: Record<string, any> = {};
    if (address.firstName) payload.first_name = address.firstName;
    if (address.lastName) payload.last_name = address.lastName;
    if (address.street) payload.street = address.street;
    if (address.apartment !== undefined) payload.apartment = address.apartment;
    if (address.city) payload.city = address.city;
    if (address.state) payload.state = address.state;
    if (address.zipCode) payload.zip_code = address.zipCode;
    if (address.country) payload.country = address.country;
    if (address.phone) payload.phone = address.phone;
    if (address.isDefaultShipping !== undefined) payload.is_default_shipping = address.isDefaultShipping;
    if (address.isDefaultBilling !== undefined) payload.is_default_billing = address.isDefaultBilling;

    const res = await ApiClient.put<Address>(`/auth/addresses/${id}`, payload);
    return res.data;
  }

  /**
   * Delete an existing address.
   */
  static async deleteAddress(id: string): Promise<void> {
    await ApiClient.delete(`/auth/addresses/${id}`);
  }
}

export class CustomersService {
  static async getCurrent(): Promise<User> {
    return AuthService.getCurrentUser();
  }
}
