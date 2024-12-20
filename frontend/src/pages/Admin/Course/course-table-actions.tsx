import PopupModal from "@/components/ui/popup-modal";
import CourseCreateForm from "./_components/course-create-form";

import TableSearchInput from "@/components/ui/table/table-search-input";
import React from "react";

const CourseTableActions = ({
  token,
  setErrors,
  fetchWithErrorHandling,
}: {
  token: string;
  setErrors: React.Dispatch<React.SetStateAction<string[]>>;
  fetchWithErrorHandling: (url: string, headers?: any) => Promise<any>;
}) => {
  return (
    <div className="flex items-center justify-between gap-2 py-5">
      <div className="flex flex-1 gap-4">
        <TableSearchInput placeholder="Search..." />
      </div>
      <div className="flex gap-3">
        <PopupModal
          renderModal={(onClose) => (
            <CourseCreateForm
              token={token}
              setErrors={setErrors}
              fetchWithErrorHandling={fetchWithErrorHandling}
              modalClose={onClose}
            />
          )}
        />
      </div>
    </div>
  );
};

export default CourseTableActions;
