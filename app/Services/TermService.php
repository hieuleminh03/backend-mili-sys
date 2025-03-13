<?php

namespace App\Services;

use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TermService
{
    /**
     * lấy tất cả các học kỳ, sắp xếp theo ngày bắt đầu giảm dần
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllTerms(): Collection
    {
        return Term::orderBy('start_date', 'desc')->get();
    }

    /**
     * lấy một học kỳ cụ thể cùng với các lớp học
     *
     * @param int $id
     * @return Term
     * @throws ModelNotFoundException
     */
    public function getTerm(int $id): Term
    {
        try {
            return Term::with('courses')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException("Không tìm thấy học kỳ với ID: $id");
        }
    }

    /**
     * thêm mới một học kỳ, có validate dữ liệu và kiểm tra trùng tên
     *
     * @param array $data dữ liệu học kỳ
     * @return Term
     * @throws \Exception
     */
    public function createTerm(array $data): Term
    {
        try {
            // transaction start ở đây
            return DB::transaction(function () use ($data) {
                // kiểm tra nếu tên học kỳ đã tồn tại (kể cả các bản ghi đã bị xóa mềm)
                $existingTerm = Term::withTrashed()->where('name', $data['name'])->first();
                if ($existingTerm) {
                    // Nếu bản ghi đã bị xóa mềm, khôi phục nó
                    if ($existingTerm->trashed()) {
                        $existingTerm->restore();
                        // Cập nhật dữ liệu mới
                        $existingTerm->fill($data);
                        
                        // Kiểm tra các ràng buộc nghiệp vụ
                        $this->validateTermBusinessRules($existingTerm);
                        
                        $existingTerm->save();
                        return $existingTerm;
                    } else {
                        throw new ValidationException(
                            validator(['name' => $data['name']], ['name' => 'unique:terms,name'])
                        );
                    }
                }
                
                $term = new Term($data);
                
                // Kiểm tra các ràng buộc nghiệp vụ
                $this->validateTermBusinessRules($term);
                
                $term->save();
                return $term;
            });
        } catch (ValidationException $e) {
            // đảm bảo ValidationException được ném lại để BaseController xử lý
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            // ghi log lỗi database
            \Log::error('Lỗi database khi tạo học kỳ: ' . $e->getMessage());
            
            // kiểm tra nếu là lỗi unique constraint
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new ValidationException(
                    validator(['name' => $data['name']], ['name' => 'unique:terms,name'])
                );
            }
            
            throw $e;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Không tìm thấy học kỳ');
        } catch (\Exception $e) {
            // ghi log lỗi khác
            \Log::error('Lỗi không xác định khi tạo học kỳ: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * cập nhật một học kỳ có validate
     *
     * @param int $id
     * @param array $data
     * @return Term
     * @throws \Exception
     */
    public function updateTerm(int $id, array $data): Term
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $term = Term::findOrFail($id);
                $term->fill($data);
                
                // Kiểm tra các ràng buộc nghiệp vụ
                $this->validateTermBusinessRules($term);
                
                $term->save();
                return $term;
            });
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException("Không tìm thấy học kỳ với ID: $id");
        }
    }

    /**
     * xóa một học kỳ
     * chỉ có thể xóa học kỳ không có lớp học
     * sử dụng forceDelete để xóa hoàn toàn khỏi database
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteTerm(int $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $term = Term::findOrFail($id);
                
                // check nếu có lớp học liên kết với học kỳ
                if ($term->courses()->count() > 0) {
                    throw new \Exception('Không thể xóa học kỳ có lớp học');
                }
                
                // Sử dụng forceDelete để xóa hoàn toàn, không phải soft delete
                return $term->forceDelete();
            });
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException("Không tìm thấy học kỳ với ID: $id");
        }
    }

    /**
     * Kiểm tra các ràng buộc nghiệp vụ cho học kỳ
     * 
     * @param Term $term
     * @throws \Exception
     */
    protected function validateTermBusinessRules(Term $term)
    {
        // check định dạng tên học kỳ
        if (!Term::isValidNameFormat($term->name)) {
            throw new \Exception('Tên học kỳ phải theo định dạng YYYY[A-Z] (ví dụ: 2024A)');
        }
        
        // check ngày tháng
        $dateValidation = $this->validateDates($term);
        if ($dateValidation !== true) {
            throw new \Exception('Lỗi kiểm tra ngày tháng: ' . implode(', ', $dateValidation));
        }
        
        // check trùng thời gian
        if ($this->hasTermOverlap($term)) {
            throw new \Exception('Thời gian của học kỳ chồng chéo với một học kỳ khác');
        }
    }

    /**
     * kiểm tra xem ngày tháng của học kỳ có hợp lệ hay không
     * 
     *
     * @param Term $term
     * @return array|true Array of errors or true if valid
     */
    protected function validateDates(Term $term)
    {
        $errors = [];
        
        // ngày kết thúc phải sau ngày bắt đầu
        if ($term->end_date <= $term->start_date) {
            $errors[] = 'ngày kết thúc phải sau ngày bắt đầu';
        }
        
        // hạn chốt lớp phải sau ngày bắt đầu ít nhất 2 tuần
        $minRosterDeadline = Carbon::parse($term->start_date)->addWeeks(2);
        if ($term->roster_deadline < $minRosterDeadline) {
            $errors[] = 'hạn chốt lớp phải sau ngày bắt đầu ít nhất 2 tuần';
        }
        
        // hạn chốt lớp phải trước ngày kết thúc
        if ($term->roster_deadline >= $term->end_date) {
            $errors[] = 'hạn chốt lớp phải trước ngày kết thúc';
        }
        
        // ngày nhập điểm phải sau ngày kết thúc ít nhất 2 tuần
        $minGradeEntryDate = Carbon::parse($term->end_date)->addWeeks(2);
        if ($term->grade_entry_date < $minGradeEntryDate) {
            $errors[] = 'ngày nhập điểm phải sau ngày kết thúc ít nhất 2 tuần';
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * kiểm tra xem một học kỳ có trùng thời gian với học kỳ khác
     *
     * @param Term $term
     * @return bool
     */
    protected function hasTermOverlap(Term $term): bool
    {
        $query = Term::where('id', '!=', $term->id ?? 0)
            ->where(function ($query) use ($term) {
                $query->whereBetween('start_date', [$term->start_date, $term->end_date])
                    ->orWhereBetween('end_date', [$term->start_date, $term->end_date])
                    ->orWhere(function ($query) use ($term) {
                        $query->where('start_date', '<=', $term->start_date)
                            ->where('end_date', '>=', $term->end_date);
                    });
            });
            
        return $query->exists();
    }
} 