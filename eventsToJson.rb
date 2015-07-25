#!/usr/bin/env ruby

require 'json'

events = Array.new

Dir['events/*.txt'].each do |item|
    next if item == '.' or item == '..'

    eventName = File.basename(item, '.txt')
    description = ""
    dataType = ""
    varName = ""
    params = Array.new

    text = File.open(item).read
    text.gsub!(/\r\n?/, "\n")

    text.each_line do |line|
        descRegex = /\t\%s\s\%s\/\/\s(.+)\%s/
        dtVarRegex = /\t(bz_[a-zA-Z]+_V[0-9])\*\s([a-zA-Z]+).+/
        dataRegex = /\t+\/\/\s+\((.+)\)\s+(.+)\s-\s(.+)/

        objDescription = ""
        objDataType = ""
        objVarName = ""

        if line =~ descRegex
            description = line.scan(descRegex)[0][0]
        elsif line =~ dtVarRegex
            dataType, varName = line.scan(dtVarRegex).flatten
        elsif line =~ dataRegex
            objDataType, objVarName, objDescription = line.scan(dataRegex).flatten

            params.push({
                'dataType' => objDataType,
                'name' => objVarName.strip,
                'desc' => objDescription
            })
        end
    end

    events.push({
        'eventName' => eventName,
        'desc' => description,
        'dataType' => dataType,
        'varName' => varName,
        'params' => params
    })
end

puts events.to_json