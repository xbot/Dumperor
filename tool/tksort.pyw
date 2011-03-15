#!/usr/bin/python
# -*- coding: utf-8 -*-

import Tkinter as tk
import tkFileDialog as fdlg
import re
import os

class Window(tk.Frame):

    def __init__(self, master):
        tk.Frame.__init__(self, master)

        self.lblFileInput = tk.Label(self, text=u"Choose a file: ")
        self.lblFileInput.grid(row=0, column=0)

        self.entryFileInput = tk.Entry(self)
        self.entryFileInput.grid(row=0, column=1)

        self.btnFileInput = tk.Button(self, text=u"Open", command=self.askopenfilename)
        self.btnFileInput.grid(row=0, column=2, padx=5, pady=5)

        self.pnlBtns = tk.Frame(self)

        #self.btnCheck = tk.Button(self.pnlBtns, text=u"检查")
        #self.btnCheck.grid(row=0, column=0, padx=5, pady=3)

        self.btnFix = tk.Button(self.pnlBtns, text=u"Sort", command=self.sort)
        self.btnFix.grid(row=0, column=1, padx=5, pady=3)

        self.pnlBtns.grid(row=1, column=0, columnspan=3)

    def askopenfilename(self):
        filename = fdlg.askopenfilename(parent=self)
        if filename:
            self.entryFileInput.delete(0, tk.END)
            self.entryFileInput.insert(0, filename)
            return True
        else:
            print 'No file selected !'
            return False

    def sort(self):
        filename = self.entryFileInput.get()
        pathinfo = os.path.splitext(filename)
        
        f = file(filename, 'r')
        o = file(pathinfo[0]+'.output.txt', 'a')

        step = 0
        rows = []
        r = re.compile(r'^[-]{10}.*$')

        for line in f.readlines():
            if line == "\n":
                rows.sort()
                for row in rows:
                    o.write(row)
                o.write("\n")
                    
                step = 0
                rows = []
            elif r.match(line):
                step = 1
                o.write(line)
            else:
                if step == 0:
                    o.write(line)
                else:
                    rows.append(line)

        f.close()
        o.close()

class App:

    def __init__(self, master):
        master.title(u"Dumperor Sorting Tool")
        
        self.window = Window(master)
        self.window.pack()

if __name__ == '__main__':
    root = tk.Tk()
    app = App(root)
    root.mainloop()
