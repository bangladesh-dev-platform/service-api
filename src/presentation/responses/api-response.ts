import { Response } from 'express';

export class ApiResponse {
    static success(res: Response, data: any, message?: string, statusCode: number = 200) {
        return res.status(statusCode).json({
            success: true,
            data,
            message
        });
    }

    static error(res: Response, message: string, statusCode: number = 500, details?: any) {
        return res.status(statusCode).json({
            success: false,
            error: {
                message,
                details
            }
        });
    }

    static paginated(res: Response, data: any[], page: number, perPage: number, total: number) {
        return res.status(200).json({
            success: true,
            data,
            pagination: {
                page,
                per_page: perPage,
                total,
                total_pages: Math.ceil(total / perPage)
            }
        });
    }
}
