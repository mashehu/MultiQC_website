import classnames from 'classnames';
import React, { useState } from 'react';

import MultiQCIconSrc from '../../images/icons/multiqc.svg';

const ExamplesBrowser = ({ items }) => {
  const [ active, setActive ] = useState(0);

  return (
    <div className="border border-gray-200 rounded-md overflow-hidden">
      <div className="bg-gray-100 border-b border-gray-200 flex">
        <div className="flex px-4 py-3 lg:w-[250px]">
          <img src={MultiQCIconSrc} className="h-6 w-6 mr-2" />
          <span className="typo-small">
            MultiQC Example Reports
          </span>
        </div>
        <div className="flex pt-2">
          {items.map((item, index) => (
            <div>
              <button
                className={classnames('typo-small bg-gray-2 px-4 h-full rounded-t-sm', {
                  'hover:bg-white': index != active,
                  'bg-gray-200 hover:bg-gray-200': index === active,
                })}
                onClick={() => { setActive(index) }}
              >
                {item.title}
              </button>
            </div>
          ))}
        </div>
      </div>
      <div>
        <iframe src={items[active].path} className="w-full h-[600px]" />
      </div>
    </div>
  );
};

export default ExamplesBrowser;
